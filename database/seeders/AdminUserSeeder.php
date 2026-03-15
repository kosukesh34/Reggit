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
        // .env の認証情報でシステム管理者を1件必ず作成/更新（DEMO_SEED 時は DEMO_SYSTEM_*、それ以外は ADMIN_*）
        $useDemo = filter_var(env('DEMO_SEED', false), FILTER_VALIDATE_BOOLEAN);
        if ($useDemo) {
            $loginId = env('DEMO_SYSTEM_LOGIN_ID', 'demo_sys');
            $password = self::demoSystemPassword();
            $name = env('DEMO_SYSTEM_NAME', 'デモシステム管理者');
            $email = env('DEMO_SYSTEM_EMAIL', $loginId.'@example.com');
        } else {
            $loginId = env('ADMIN_LOGIN_ID', env('ADMIN_EMAIL', 'reserve'));
            $password = env('ADMIN_PASSWORD', 'reserve');
            $name = env('ADMIN_NAME', 'システムアドミン');
            $email = $loginId.'@system.local';
        }

        AdminUser::updateOrCreate(
            ['login_id' => $loginId],
            [
                'email' => $email,
                'password' => $password,
                'name' => $name,
                'role' => AdminUser::ROLE_SYSTEM_ADMIN,
                'project_id' => null,
            ]
        );

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
}
