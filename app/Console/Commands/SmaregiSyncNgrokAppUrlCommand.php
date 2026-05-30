<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

class SmaregiSyncNgrokAppUrlCommand extends Command
{
    protected $signature = 'smaregi:sync-ngrok-url
                            {--url= : ngrok の公開 URL（未指定時は localhost:4040 から取得）}
                            {--dry-run : .env を更新せず表示のみ}';

    protected $description = 'ngrok の公開 URL を APP_URL に同期し、スマレジ Webhook 用 URL を表示する';

    public function handle(): int
    {
        $url = $this->option('url');
        if (! is_string($url) || trim($url) === '') {
            $url = $this->fetchNgrokPublicUrl();
        } else {
            $url = trim($url);
        }

        if ($url === null || $url === '') {
            $this->error('ngrok の公開 URL を取得できませんでした。');
            $this->line('  1. ホストで ngrok を起動: ./start-ngrok.sh');
            $this->line('  2. または --url=https://xxxx.ngrok-free.dev を指定');

            return self::FAILURE;
        }

        $url = rtrim($url, '/');
        if (! str_starts_with($url, 'https://')) {
            $this->error('APP_URL は https で始まる必要があります: '.$url);

            return self::FAILURE;
        }

        $webhookUrl = $url.'/api/smaregi/webhook';
        $idpUrl = $url.'/api/smaregi/idp';

        $this->info('ngrok 公開 URL: '.$url);
        $this->info('Webhook URL（スマレジに登録）: '.$webhookUrl);
        $this->info('利用者契約通知先 URL: '.$idpUrl);
        $this->newLine();

        if ($this->option('dry-run')) {
            $this->comment('dry-run のため .env は更新しません。');

            return self::SUCCESS;
        }

        if (! $this->updateEnvAppUrl($url)) {
            $this->error('.env の APP_URL を更新できませんでした。');

            return self::FAILURE;
        }

        $this->info('.env の APP_URL を更新しました。');
        Artisan::call('config:clear');
        $this->comment('config:clear を実行しました。');
        $this->newLine();
        $this->warn('スマレジ・デベロッパーズの「Webhook送信先エンドポイント」を上記 URL に設定してください。');

        return self::SUCCESS;
    }

    private function fetchNgrokPublicUrl(): ?string
    {
        try {
            $response = Http::timeout(3)->get('http://host.docker.internal:4040/api/tunnels');
            if (! $response->successful()) {
                $response = Http::timeout(3)->get('http://127.0.0.1:4040/api/tunnels');
            }
        } catch (\Throwable) {
            try {
                $response = Http::timeout(3)->get('http://127.0.0.1:4040/api/tunnels');
            } catch (\Throwable) {
                return null;
            }
        }

        if (! $response->successful()) {
            return null;
        }

        $tunnels = $response->json('tunnels');
        if (! is_array($tunnels)) {
            return null;
        }

        foreach ($tunnels as $tunnel) {
            if (! is_array($tunnel)) {
                continue;
            }
            $public = $tunnel['public_url'] ?? null;
            if (is_string($public) && str_starts_with($public, 'https://')) {
                return $public;
            }
        }

        return null;
    }

    private function updateEnvAppUrl(string $url): bool
    {
        $envPath = base_path('.env');
        if (! is_readable($envPath) || ! is_writable($envPath)) {
            return false;
        }

        $content = (string) file_get_contents($envPath);
        $escaped = preg_quote('=', '/');
        $newLine = 'APP_URL='.$url;

        if (preg_match('/^APP_URL=.*$/m', $content)) {
            $content = (string) preg_replace('/^APP_URL=.*$/m', $newLine, $content);
        } else {
            $content .= "\n".$newLine."\n";
        }

        return file_put_contents($envPath, $content) !== false;
    }
}
