<?php

namespace App\Console\Commands;

use App\Models\UserDevice;
use Illuminate\Console\Command;

class ClearPushDevicesCommand extends Command
{
    protected $signature = 'push:clear-devices
                            {--force : 確認なしで削除}';

    protected $description = 'user_devices の全件を削除する（端末の再登録をやり直すとき用）';

    public function handle(): int
    {
        $count = UserDevice::query()->count();

        if ($count === 0) {
            $this->info('user_devices は既に 0 件です。');
            return self::SUCCESS;
        }

        $this->warn("user_devices を {$count} 件削除します。アプリ側で再度ログイン・通知許可すると再登録されます。");

        if (! $this->option('force') && ! $this->confirm('実行しますか？', false)) {
            $this->info('キャンセルしました。');
            return self::SUCCESS;
        }

        UserDevice::query()->delete();
        $this->info('user_devices を 0 件にしました。');

        return self::SUCCESS;
    }
}
