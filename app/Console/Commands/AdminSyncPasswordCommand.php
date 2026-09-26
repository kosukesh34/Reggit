<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Console\Command;

class AdminSyncPasswordCommand extends Command
{
    protected $signature = 'admin:sync-password
                            {login_id? : 対象の login_id（省略時は demo_sys または .env の DEMO_SYSTEM_LOGIN_ID）}';

    protected $description = '.env のパスワードで管理ユーザーのパスワードを DB に同期する（ログインできない場合に実行）';

    public function handle(): int
    {
        $loginId = $this->argument('login_id');
        if ($loginId === null || $loginId === '') {
            $loginId = config('admin.demo.system_login_id');
        }

        $user = AdminUser::where('login_id', $loginId)->first();
        if ($user === null) {
            $demoLoginId = config('admin.demo.system_login_id');
            if ($loginId === $demoLoginId && app()->environment('production') === false) {
                AdminUserSeeder::upsertDocumentedDemoSystemAdmin();
                $user = AdminUser::where('login_id', $loginId)->first();
            }
        }
        if ($user === null) {
            $this->error("login_id [{$loginId}] のユーザーが存在しません。先に php artisan db:seed --class=AdminUserSeeder を実行してください。");

            return self::FAILURE;
        }

        $password = match ($loginId) {
            config('admin.demo.system_login_id') => AdminUserSeeder::demoSystemPassword(),
            config('admin.demo.store_login_id') => (string) config('admin.demo.store_password'),
            config('admin.demo.project_login_id') => (string) config('admin.demo.project_password'),
            config('admin.demo.staff_login_id') => (string) config('admin.demo.staff_password'),
            default => (string) config('admin.password'),
        };

        if ($password === '') {
            $this->error('.env に対象ユーザーのパスワード（DEMO_*_PASSWORD または ADMIN_PASSWORD）を設定してください。');

            return self::FAILURE;
        }

        $user->password = $password;
        $user->save();

        $this->info("login_id [{$loginId}] のパスワードを .env の値で更新しました。");

        return self::SUCCESS;
    }
}
