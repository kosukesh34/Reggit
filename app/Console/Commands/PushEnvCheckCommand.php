<?php

namespace App\Console\Commands;

use App\Models\UserDevice;
use Illuminate\Console\Command;

class PushEnvCheckCommand extends Command
{
    protected $signature = 'push:check-env';

    protected $description = 'プッシュ通知が送れる環境かどうか確認する（送信は行いません）';

    public function handle(): int
    {
        $appId = trim((string) config('push.onesignal.app_id'));
        $apiKey = trim((string) config('push.onesignal.api_key'));

        $ok = true;

        if ($appId === '') {
            $this->error('PUSH_ONESIGNAL_APP_ID が未設定です。.env に設定してください。');
            $ok = false;
        } else {
            $this->info('PUSH_ONESIGNAL_APP_ID: '.substr($appId, 0, 8).'...'.substr($appId, -4).' （iOS Info.plist の OneSignalAppId と一致させる）');
        }

        if ($apiKey === '') {
            $this->error('PUSH_ONESIGNAL_API_KEY が未設定です。OneSignal Dashboard > Settings > Keys & IDs の REST API Key を .env に設定してください。');
            $ok = false;
        } else {
            $this->info('PUSH_ONESIGNAL_API_KEY: 設定済み');
        }

        $deviceCount = UserDevice::query()->whereNotNull('push_token')->where('push_token', '!=', '')->count();
        $this->info('登録端末（push_token あり）: '.$deviceCount.' 件');

        if (! $ok) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->comment('送信テスト: php artisan push:onesignal-test --force --segment');
        $this->comment('端末削除: php artisan push:clear-devices --force');

        return self::SUCCESS;
    }
}
