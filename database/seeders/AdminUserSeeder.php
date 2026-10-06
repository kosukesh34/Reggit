<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /** .env で # がコメント扱いされて切れた場合でもドキュメント通りのパスワードで作成する */
    public static function demoSystemPassword(): string
    {
        $p = env('DEMO_SYSTEM_PASSWORD');
        if ($p === null || $p === '' || $p === 'DemoSys') {
            return 'DemoSys#2026';
        }

        return $p;
    }

    public function run(): void
    {
        // Production must never provision accounts with documented demo credentials.
        $useDemo = filter_var(env('DEMO_SEED', false), FILTER_VALIDATE_BOOLEAN);
        if ($useDemo && app()->environment('production')) {
            throw new \RuntimeException('DEMO_SEED cannot be enabled in production.');
        }
        if ($useDemo) {
            $loginId = env('DEMO_SYSTEM_LOGIN_ID', 'demo_sys');
            $password = self::demoSystemPassword();
            $name = env('DEMO_SYSTEM_NAME', 'デモシステム管理者');
            $email = env('DEMO_SYSTEM_EMAIL', $loginId.'@example.com');
        } else {
            $loginId = trim((string) (env('ADMIN_LOGIN_ID') ?: env('ADMIN_EMAIL', '')));
            $password = (string) env('ADMIN_PASSWORD', '');
            if ($loginId === '') {
                throw new \RuntimeException('ADMIN_LOGIN_ID or ADMIN_EMAIL is required.');
            }
            $name = env('ADMIN_NAME', 'システムアドミン');
            $email = $loginId.'@system.local';
        }

        // Re-running a seed must not reset passwords or elevate an existing account.
        if (! AdminUser::where('login_id', $loginId)->exists()) {
            if (! $useDemo && (trim($password) === '' || $password === 'reserve')) {
                throw new \RuntimeException('Set a non-default ADMIN_PASSWORD before creating a system administrator.');
            }
            AdminUser::create([
                'login_id' => $loginId,
                'email' => $email,
                'password' => $password,
                'name' => $name,
                'role' => AdminUser::ROLE_SYSTEM_ADMIN,
                'project_id' => null,
            ]);
        }

        // DEMO_SEED=false でも非本番ではドキュメント通り demo_sys でログインできるようにする
        if ($useDemo === false && app()->environment('production') === false) {
            self::upsertDocumentedDemoSystemAdmin();
        }

        // 初回のみデフォルトプロジェクトを作成（DemoAdminUsersSeeder で使う）
        if (! Project::where('is_active', true)->exists()) {
            Project::create([
                'project_key' => 'default',
                'name' => 'デフォルト店舗',
                'description' => 'デモ用プロジェクト',
                'is_active' => true,
            ]);
        }
    }

    /** ドキュメント記載のデモ用システム管理者（既定: demo_sys）を DB に反映する */
    public static function upsertDocumentedDemoSystemAdmin(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo administrators cannot be seeded in production.');
        }

        $loginId = env('DEMO_SYSTEM_LOGIN_ID', 'demo_sys');
        AdminUser::firstOrCreate(
            ['login_id' => $loginId],
            [
                'email' => env('DEMO_SYSTEM_EMAIL', $loginId.'@example.com'),
                'password' => self::demoSystemPassword(),
                'name' => env('DEMO_SYSTEM_NAME', 'デモシステム管理者'),
                'role' => AdminUser::ROLE_SYSTEM_ADMIN,
                'project_id' => null,
            ]
        );
    }
}
