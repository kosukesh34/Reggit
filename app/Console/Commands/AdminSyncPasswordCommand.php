<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\Concerns\UpsertsAdminUsers;
use Database\Seeders\DemoAdminUsersSeeder;
use Illuminate\Console\Command;

class AdminSyncPasswordCommand extends Command
{
    use UpsertsAdminUsers;

    protected $signature = 'admin:sync-password
                            {login_id? : 対象の login_id（省略時は demo_sys または .env の DEMO_SYSTEM_LOGIN_ID）}
                            {--all : .env のデモ用・ADMIN 用アカウントをまとめて同期する}';

    protected $description = '.env のパスワードで管理ユーザーのパスワードを DB に同期する（ログインできない場合に実行）';

    public function handle(): int
    {
        if ($this->option('all')) {
            return $this->syncAllConfiguredAdmins();
        }

        $loginId = $this->argument('login_id');
        if ($loginId === null || $loginId === '') {
            $loginId = env('DEMO_SYSTEM_LOGIN_ID', 'demo_sys');
        }

        return $this->syncLoginId($loginId) ? self::SUCCESS : self::FAILURE;
    }

    private function syncAllConfiguredAdmins(): int
    {
        if (AdminUser::count() === 0) {
            $this->call('db:seed', ['--class' => AdminUserSeeder::class]);
            if (self::isDemoSeedEnabled()) {
                $this->call('db:seed', ['--class' => DemoAdminUsersSeeder::class]);
            }
        }

        $targets = $this->configuredAdminCredentials();
        $failed = 0;
        foreach ($targets as $loginId => $password) {
            if ($this->syncLoginId($loginId, $password) === false) {
                $failed++;
            }
        }

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<string, string> */
    private function configuredAdminCredentials(): array
    {
        $credentials = [
            env('DEMO_SYSTEM_LOGIN_ID', 'demo_sys') => AdminUserSeeder::demoSystemPassword(),
        ];

        if (self::isDemoSeedEnabled()) {
            $credentials[env('DEMO_PROJECT_LOGIN_ID', 'demo_proj')] = self::resolveDemoPassword(
                'DEMO_PROJECT_PASSWORD',
                'DemoProj#2026',
                ['DemoProj'],
            );
            $credentials[env('DEMO_STORE_LOGIN_ID', 'demo_store')] = self::resolveDemoPassword(
                'DEMO_STORE_PASSWORD',
                'DemoStore#2026',
                ['DemoStore'],
            );
            $credentials[env('DEMO_STAFF_LOGIN_ID', 'demo_staff')] = self::resolveDemoPassword(
                'DEMO_STAFF_PASSWORD',
                'DemoStaff#2026',
                ['DemoStaff'],
            );
        }

        $adminLoginId = env('ADMIN_LOGIN_ID', env('ADMIN_EMAIL', 'reserve'));
        if (is_string($adminLoginId) && $adminLoginId !== '') {
            $credentials[$adminLoginId] = env('ADMIN_PASSWORD', 'reserve');
        }

        return $credentials;
    }

    private function syncLoginId(string $loginId, ?string $password = null): bool
    {
        $user = AdminUser::where('login_id', $loginId)->first();
        if ($user === null) {
            $demoLoginId = env('DEMO_SYSTEM_LOGIN_ID', 'demo_sys');
            if ($loginId === $demoLoginId && app()->environment('production') === false) {
                AdminUserSeeder::upsertDocumentedDemoSystemAdmin();
                $user = AdminUser::where('login_id', $loginId)->first();
            }
        }
        if ($user === null) {
            $this->upsertConfiguredSystemAdmin($loginId, $password);
            $user = AdminUser::where('login_id', $loginId)->first();
        }
        if ($user === null) {
            $this->error("login_id [{$loginId}] のユーザーが存在しません。先に php artisan db:seed を実行してください。");

            return false;
        }

        $password ??= $this->resolvePasswordForLoginId($loginId);

        if ($password === '' || $password === null) {
            $this->error("login_id [{$loginId}] 用のパスワードが .env に設定されていません。");

            return false;
        }

        $user->password = $password;
        $user->save();

        $this->info("login_id [{$loginId}] のパスワードを .env の値で更新しました。");

        return true;
    }

    private function upsertConfiguredSystemAdmin(string $loginId, ?string $password): void
    {
        $adminLoginId = env('ADMIN_LOGIN_ID', env('ADMIN_EMAIL', 'reserve'));
        if ($loginId !== $adminLoginId) {
            return;
        }

        self::upsertAdminWithoutResettingPassword(
            $loginId,
            $loginId.'@system.local',
            $password ?? env('ADMIN_PASSWORD', 'reserve'),
            env('ADMIN_NAME', 'システムアドミン'),
            AdminUser::ROLE_SYSTEM_ADMIN,
        );
    }

    private function resolvePasswordForLoginId(string $loginId): string
    {
        foreach ($this->configuredAdminCredentials() as $configuredLoginId => $password) {
            if ($configuredLoginId === $loginId) {
                return $password;
            }
        }

        return env('ADMIN_PASSWORD', 'reserve');
    }
}
