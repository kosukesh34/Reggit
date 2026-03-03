<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;

class SendOneSignalTestCommand extends Command
{
    protected $signature = 'push:onesignal-test
                            {--title= : 通知タイトル（省略時: テスト通知）}
                            {--body= : 通知本文（省略時: これはテストです）}
                            {--force : 確認なしで送信}';

    protected $description = 'OneSignal で全登録端末にテストプッシュ通知を送信する';

    public function handle(): int
    {
        $appId = trim((string) config('push.onesignal.app_id'));
        $apiKey = trim((string) config('push.onesignal.api_key'));
        $apiUrl = rtrim((string) config('push.onesignal.api_url'), '/');

        if ($appId === '' || $apiKey === '') {
            $this->error('OneSignal が未設定です。.env に PUSH_ONESIGNAL_APP_ID と PUSH_ONESIGNAL_API_KEY を設定してください。');
            return self::FAILURE;
        }

        if (! (bool) config('push.onesignal.enabled')) {
            $this->warn('PUSH_ONESIGNAL_ENABLED が false です。送信を続行します。');
        }

        $title = $this->option('title') ?: 'テスト通知';
        $body = $this->option('body') ?: 'これはテストです';

        $this->info("送信内容: タイトル={$title}, 本文={$body}");
        if (! $this->option('force') && ! $this->confirm('この内容で OneSignal に送信しますか？', true)) {
            $this->info('キャンセルしました。');
            return self::SUCCESS;
        }

        $client = new Client(['timeout' => 15]);
        try {
            $response = $client->post($apiUrl, [
                'headers' => [
                    'Authorization' => 'Key ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'app_id' => $appId,
                    'included_segments' => ['All'],
                    'target_channel' => 'push',
                    'headings' => ['en' => $title],
                    'contents' => ['en' => $body],
                    'data' => [
                        'test' => true,
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            $this->error('OneSignal リクエストに失敗しました: ' . $e->getMessage());
            return self::FAILURE;
        }

        $payload = json_decode((string) $response->getBody(), true);
        $recipients = $payload['recipients'] ?? 0;
        $id = $payload['id'] ?? null;

        $this->info("送信リクエストが完了しました。配信先: {$recipients} 件" . ($id ? " (notification id: {$id})" : ''));

        return self::SUCCESS;
    }
}
