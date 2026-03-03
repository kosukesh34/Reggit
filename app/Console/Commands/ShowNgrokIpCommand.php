<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ShowNgrokIpCommand extends Command
{
    protected $signature = 'app:show-ngrok-ip
                            {--log : ログファイルにも出力する（storage/logs/laravel.log）}';

    protected $description = 'APP_URL（ngrok 等）のホスト名を解決し、登録用の IP アドレスを表示する';

    public function handle(): int
    {
        $appUrl = trim((string) config('app.url'));
        if ($appUrl === '') {
            $this->error('APP_URL が未設定です。.env を確認してください。');
            return self::FAILURE;
        }

        $host = parse_url($appUrl, PHP_URL_HOST);
        if (! is_string($host) || $host === '') {
            $this->error('APP_URL からホスト名を取得できませんでした: ' . $appUrl);
            return self::FAILURE;
        }

        $this->info('APP_URL: ' . $appUrl);
        $this->info('ホスト名: ' . $host);
        $this->newLine();

        $ips = $this->resolveHostToIps($host);
        if ($ips === []) {
            $this->warn('IP アドレスを取得できませんでした（DNS 解決失敗または IPv6 のみの可能性）。');
            $this->comment('手動で確認: nslookup ' . $host);
            $message = "ngrok IP resolve: host={$host} ips=(none)";
        } else {
            $this->info('登録用 IP アドレス（ngrok 側）:');
            foreach ($ips as $ip) {
                $this->line('  ' . $ip);
            }
            $this->newLine();
            $this->comment('上記の IP を、ngrok 側の IP 登録が必要なサービス（日本郵便など）に登録してください。');
            $message = 'ngrok IP resolve: host=' . $host . ' ips=' . implode(',', $ips);
        }

        if ($this->option('log')) {
            Log::info($message, ['app_url' => $appUrl, 'host' => $host, 'ips' => $ips]);
            $this->info('ログに出力しました: storage/logs/laravel.log');
        } else {
            $this->comment('ログにも出力する場合: php artisan app:show-ngrok-ip --log');
        }

        return self::SUCCESS;
    }

    /**
     * ホスト名を IPv4 アドレスに解決する。
     *
     * @return array<int, string>
     */
    private function resolveHostToIps(string $host): array
    {
        $ips = @gethostbynamel($host);
        if (is_array($ips)) {
            return $ips;
        }
        $single = @gethostbyname($host);
        if ($single !== $host && $single !== '') {
            return [$single];
        }

        return [];
    }
}
