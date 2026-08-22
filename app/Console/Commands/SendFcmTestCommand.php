<?php

namespace App\Console\Commands;

use App\Models\UserDevice;
use App\Services\FcmHttpV1Client;
use App\Services\PushNotificationService;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class SendFcmTestCommand extends Command
{
    protected $signature = 'push:fcm-test
                            {--title= : 通知タイトル（省略時: テスト通知）}
                            {--body= : 通知本文（省略時: これは FCM テストです）}
                            {--force : 確認なしで送信}';

    protected $description = 'FCM HTTP v1 で登録端末（user_devices の push_token）にテストプッシュを送信する';

    public function handle(): int
    {
        $client = new FcmHttpV1Client(new Client(['timeout' => 15]));
        if (! $client->isConfigured()) {
            $this->error('FCM が未設定です。.env に PUSH_FCM_ENABLED=true, PUSH_FCM_PROJECT_ID, PUSH_FCM_CREDENTIALS を設定してください。');

            return self::FAILURE;
        }

        $title = $this->option('title') ?: 'テスト通知';
        $body = $this->option('body') ?: 'これは FCM テストです';

        $tokens = UserDevice::query()
            ->whereNotNull('push_token')
            ->where('push_token', '!=', '')
            ->pluck('push_token')
            ->map(fn ($t) => trim((string) $t))
            ->filter(fn ($t) => $t !== '')
            ->unique()
            ->values()
            ->all();

        $this->info("送信内容: タイトル={$title}, 本文={$body}");
        $this->info('配信先: '.count($tokens).' 件');
        $this->line('project_id: '.(string) config('push.fcm.project_id'));

        if ($tokens === []) {
            $this->warn('登録端末が 0 件です。アプリでログインし、通知を許可して FCM トークンを再登録してください。');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('この内容で送信しますか？', true)) {
            $this->info('キャンセルしました。');

            return self::SUCCESS;
        }

        $service = new PushNotificationService(new Client(['timeout' => 15]));
        $result = $service->sendToSubscriptionIds(
            $tokens,
            $title,
            $body,
            ['type' => 'fcm_test']
        );

        $this->info('sent='.$result['sent'].' failed='.$result['failed'].' skipped='.$result['skipped']);

        return $result['failed'] > 0 && $result['sent'] === 0 ? self::FAILURE : self::SUCCESS;
    }
}
