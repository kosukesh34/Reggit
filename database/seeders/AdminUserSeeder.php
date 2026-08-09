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

        // DEMO_SEED=false でも非本番ではドキュメント通り demo_sys でログインできるようにする
        if ($useDemo === false && app()->environment('production') === false) {
            self::upsertDocumentedDemoSystemAdmin();
        }

        // 初回のみデフォルトプロジェクトを作成（DemoAdminUsersSeeder で使う）
        if (! Project::where('is_active', true)->exists()) {
            Project::create([
                'project_key' => self::defaultProjectKey(),
                'name' => 'デフォルト店舗',
                'description' => 'デモ用プロジェクト',
                'is_active' => true,
            ]);
        }
    }

    /** ローカルではスマレジ契約IDを project_key にする（/liff/{contract_id} と揃える） */
    public static function defaultProjectKey(): string
    {
        $contractId = config('smaregi.contract_id');
        if (is_string($contractId)) {
            $trimmed = trim($contractId);
            if ($trimmed !== '' && preg_match('/^[a-zA-Z0-9_-]+$/', $trimmed) === 1) {
                return $trimmed;
            }
        }

        return 'default';
    }

    /** ドキュメント記載のデモ用システム管理者（既定: demo_sys）を DB に反映する */
    public static function upsertDocumentedDemoSystemAdmin(): void
    {
        $loginId = env('DEMO_SYSTEM_LOGIN_ID', 'demo_sys');
        AdminUser::updateOrCreate(
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
