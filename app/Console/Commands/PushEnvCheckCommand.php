<?php

namespace App\Console\Commands;

use App\Models\UserDevice;
use App\Services\FcmHttpV1Client;
use GuzzleHttp\Client;
use Illuminate\Console\Command;

class PushEnvCheckCommand extends Command
{
    protected $signature = 'push:check-env';

    protected $description = 'プッシュ通知が送れる環境かどうか確認する（送信は行いません）';

    public function handle(): int
    {
        $fcm = new FcmHttpV1Client(new Client(['timeout' => 5]));

        if (! $fcm->isConfigured()) {
            $this->error('FCM が未設定です。PUSH_FCM_ENABLED / PUSH_FCM_PROJECT_ID / PUSH_FCM_CREDENTIALS を確認してください。');

            return self::FAILURE;
        }

        $this->info('FCM HTTP v1: 設定済み（project_id='.config('push.fcm.project_id').'）');

        $deviceCount = UserDevice::query()->whereNotNull('push_token')->where('push_token', '!=', '')->count();
        $this->info('登録端末（push_token あり）: '.$deviceCount.' 件');

        $this->newLine();
        $this->comment('送信テスト: php artisan push:fcm-test --force');
        $this->comment('端末削除: php artisan push:clear-devices --force');

        return self::SUCCESS;
    }
}
