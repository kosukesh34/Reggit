<?php

namespace App\Console\Commands;

use App\Models\UserDevice;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class SendOneSignalTestCommand extends Command
{
    protected $signature = 'push:onesignal-test
                            {--title= : 通知タイトル（省略時: テスト通知）}
                            {--body= : 通知本文（省略時: これはテストです）}
                            {--force : 確認なしで送信}
                            {--segment : subscription_ids のほかにセグメント「Subscribed Users」にも送信（届くか切り分け用）}';

    protected $description = 'OneSignal で登録端末（user_devices の push_token）にテストプッシュ通知を送信する';

    private const CHUNK_SIZE = 2000;

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

        $subscriptionIds = UserDevice::query()
            ->whereNotNull('push_token')
            ->where('push_token', '!=', '')
            ->pluck('push_token')
            ->map(fn ($t) => trim((string) $t))
            ->filter(fn ($t) => $t !== '')
            ->unique()
            ->values()
            ->all();

        $this->info("送信内容: タイトル={$title}, 本文={$body}");
        $appIdPreview = strlen($appId) >= 8 ? substr($appId, 0, 8) . '...' . substr($appId, -4) : $appId;
        $this->line('使用中の OneSignal App ID: ' . $appIdPreview . ' （iOS の Info.plist OneSignalAppId と一致しているか確認してください）');
        $this->info('配信先: user_devices に登録された push_token ' . count($subscriptionIds) . ' 件');
        if (count($subscriptionIds) > 0) {
            $first = $subscriptionIds[0];
            $preview = strlen($first) > 12 ? substr($first, 0, 8) . '...' . substr($first, -4) : substr($first, 0, 8) . '...';
            $this->line('  (先頭の ID 例: ' . $preview . ', 長さ: ' . strlen($first) . ' 文字)');
        }

        if (count($subscriptionIds) === 0 && ! $this->option('segment')) {
            $this->warn('登録端末が 0 件です。アプリでログインし、通知を許可したうえでしばらく待ってから再度実行するか、--segment でセグメント送信を試してください。');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('この内容で送信しますか？', true)) {
            $this->info('キャンセルしました。');
            return self::SUCCESS;
        }

        $client = new Client(['timeout' => 15]);
        $totalSent = 0;
        $totalFailed = 0;

        foreach (array_chunk($subscriptionIds, self::CHUNK_SIZE) as $chunk) {
            try {
                $response = $client->post($apiUrl, [
                    'headers' => [
                        'Authorization' => 'Key ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'app_id' => $appId,
                        'include_subscription_ids' => $chunk,
                        'target_channel' => 'push',
                        'headings' => ['en' => $title],
                        'contents' => ['en' => $body],
                        'data' => ['test' => true],
                    ],
                ]);
            } catch (\Throwable $e) {
                $this->error('OneSignal リクエストに失敗しました: ' . $e->getMessage());
                return self::FAILURE;
            }

            $payload = json_decode((string) $response->getBody(), true) ?? [];
            $recipients = (int) ($payload['recipients'] ?? 0);
            $id = $payload['id'] ?? null;
            $totalSent += $recipients;
            $totalFailed += count($chunk) - $recipients;

            if ($recipients < count($chunk) && $response->getStatusCode() === 200) {
                $errors = $payload['errors'] ?? null;
                if ($errors !== null) {
                    $this->warn('OneSignal レスポンスの errors: ' . json_encode($errors, JSON_UNESCAPED_UNICODE));
                }
                if ($recipients === 0 && count($chunk) > 0) {
                    $this->warn('配信先 0 件の主な原因: (1) subscription_id が無効・期限切れ (2) 別アプリのトークン (3) 端末で通知オフ (4) OneSignal ダッシュボードで購読なし。DB の push_token が OneSignal の subscription ID（UUID）か確認してください。');
                }
            }
        }

        $this->info('送信完了。送信先: ' . $totalSent . ' 件' . ($totalFailed > 0 ? ", 失敗: {$totalFailed} 件" : ''));

        if ($this->option('segment')) {
            $this->newLine();
            $this->info('セグメント「Subscribed Users」へも送信します（App ID/Key と端末の購読状態の確認用）…');
            try {
                $segResponse = $client->post($apiUrl, [
                    'headers' => [
                        'Authorization' => 'Key ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => [
                        'app_id' => $appId,
                        'included_segments' => ['Subscribed Users'],
                        'target_channel' => 'push',
                        'headings' => ['en' => $title],
                        'contents' => ['en' => $body . ' (セグメント送信)'],
                        'data' => ['test' => true],
                    ],
                ]);
                $segPayload = json_decode((string) $segResponse->getBody(), true) ?? [];
                $segRecipients = (int) ($segPayload['recipients'] ?? 0);
                $this->info('セグメント送信結果: 配信先 ' . $segRecipients . ' 件');
                if ($segRecipients > 0 && $totalSent === 0) {
                    $this->warn('→ このアプリには購読端末がありますが、DB の subscription_id が一致していません。iOS の OneSignalAppId と .env の PUSH_ONESIGNAL_APP_ID が同じか、アプリで通知許可後に再起動して push_token を再登録してください。');
                } elseif ($segRecipients === 0) {
                    $this->newLine();
                    $this->warn('【購読 0 件】この OneSignal アプリに iOS/Android の購読端末が 1 件も登録されていません。');
                    $this->line('  1. 実機で実行しているか（シミュレータでは登録されない）');
                    $this->line('  2. OneSignal Dashboard → Settings → Platforms → Apple iOS (APNs) に p8 キーまたは証明書を設定したか');
                    $this->line('  3. その APNs の Bundle ID がアプリの Bundle ID（Skip.env の PRODUCT_BUNDLE_IDENTIFIER）と完全一致しているか');
                    $this->line('  4. 端末で通知を「許可」し、アプリを開いたまま 15〜20 秒待ったか');
                    $this->line('  詳細: mobile/mobile-order-skip/README.md の「Apple iOS (APNs) が 0 人」を参照');
                }
            } catch (\Throwable $e) {
                $this->error('セグメント送信エラー: ' . $e->getMessage());
            }
        }

        return self::SUCCESS;
    }
}
