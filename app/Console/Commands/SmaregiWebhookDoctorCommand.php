<?php

namespace App\Console\Commands;

use App\Enums\SmaregiEvent;
use App\Enums\SmaregiWebhookAction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SmaregiWebhookDoctorCommand extends Command
{
    protected $signature = 'smaregi:webhook-doctor
                            {--probe : ローカルと APP_URL へ接続テスト POST を送る}';

    protected $description = 'スマレジ Webhook 受信設定の診断（URL・シークレット・到達性）';

    public function handle(): int
    {
        $appUrl = rtrim((string) config('app.url'), '/');
        $webhookUrl = $appUrl.'/api/smaregi/webhook';
        $contractId = (string) config('smaregi.contract_id', '');
        $secret = (string) config('smaregi.webhook_secret', '');
        $secretHeader = (string) config('smaregi.webhook_secret_header', 'x-sdsch-idnect');
        $inline = (bool) config('smaregi.webhook_run_sync_inline', false);

        $this->info('=== スマレジ Webhook 診断 ===');
        $this->table(
            ['項目', '値'],
            [
                ['APP_URL', $appUrl !== '' ? $appUrl : '(未設定)'],
                ['Webhook URL', $webhookUrl],
                ['契約ID (SMAREGI_CONTRACT_ID)', $contractId !== '' ? $contractId : '(未設定)'],
                ['Webhook シークレット', $secret !== '' ? '設定済み ('.$secretHeader.')' : '未設定（検証なし）'],
                ['同期実行モード', $inline ? 'inline（local）' : 'afterResponse'],
            ]
        );

        if ($appUrl === '') {
            $this->error('APP_URL が未設定です。php artisan smaregi:sync-ngrok-url を実行してください。');

            return self::FAILURE;
        }

        if (str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1')) {
            $this->warn('APP_URL が localhost です。スマレジからは届きません。ngrok URL に sync-ngrok-url してください。');
        }

        if (! $this->option('probe')) {
            $this->newLine();
            $this->comment('接続テスト: php artisan smaregi:webhook-doctor --probe');
            $this->comment('ログ監視: docker compose exec app tail -f storage/logs/laravel-$(date +%Y-%m-%d).log | grep Smaregi');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('--- ローカル接続テスト (nginx:8080) ---');
        $localOk = $this->probeUrl('http://reggit_nginx/api/smaregi/webhook', $secret, $secretHeader, $contractId);

        if (str_contains($appUrl, 'ngrok')) {
            $this->newLine();
            $this->info('--- 公開 URL 接続テスト (APP_URL) ---');
            $publicOk = $this->probeUrl($webhookUrl, $secret, $secretHeader, $contractId);
        } else {
            $publicOk = null;
            $this->comment('APP_URL が ngrok でないため公開 URL テストはスキップしました。');
        }

        $this->newLine();
        if ($localOk === false) {
            $this->error('ローカル Webhook エンドポイントに到達できません。docker compose up を確認してください。');

            return self::FAILURE;
        }

        if ($publicOk === false) {
            $this->error('公開 URL に到達できません。ngrok 起動と smaregi:sync-ngrok-url を確認してください。');

            return self::FAILURE;
        }

        $this->info('接続テスト完了。storage/logs の [Smaregi] Webhook を確認してください。');

        return self::SUCCESS;
    }

    private function probeUrl(string $url, string $secret, string $secretHeader, string $contractId): bool
    {
        $headers = [
            'Content-Type' => 'application/json',
            'smaregi-event' => SmaregiEvent::POS_CUSTOMERS,
            'smaregi-contract-id' => $contractId !== '' ? $contractId : 'probe_contract',
            'User-Agent' => 'Smaregi-Platform-Api-Webhook/1.2.4(probe)',
        ];
        if ($secret !== '') {
            $headers[$secretHeader] = $secret;
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->post($url, [
                    'event' => SmaregiEvent::POS_CUSTOMERS,
                    'action' => SmaregiWebhookAction::EDITED,
                    'contractId' => $contractId !== '' ? $contractId : 'probe_contract',
                    'customerIds' => [],
                ]);
        } catch (\Throwable $e) {
            $this->error($url.' → 例外: '.$e->getMessage());

            return false;
        }

        $status = $response->status();
        $bodyHead = substr((string) $response->body(), 0, 120);
        $ok = $status >= 200 && $status < 300;

        if ($ok) {
            $this->info($url.' → HTTP '.$status);
        } else {
            $this->error($url.' → HTTP '.$status.' body='.$bodyHead);
        }

        if (str_contains($bodyHead, 'ngrok') && str_contains($bodyHead, 'html')) {
            $this->warn('ngrok の警告 HTML が返っています。スマレジからは届かない可能性があります。');
        }

        return $ok;
    }
}
