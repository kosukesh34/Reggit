<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Project;
use Database\Seeders\Concerns\UpsertsAdminUsers;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    use UpsertsAdminUsers;
    /** .env で # がコメント扱いされて切れた場合でもドキュメント通りのパスワードで作成する */
    public static function demoSystemPassword(): string
    {
        return self::resolveDemoPassword('DEMO_SYSTEM_PASSWORD', 'DemoSys#2026', ['DemoSys']);
    }

    public function run(): void
    {
        $useDemo = self::isDemoSeedEnabled();
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

        self::upsertAdminWithoutResettingPassword(
            $loginId,
            $email,
            $password,
            $name,
            AdminUser::ROLE_SYSTEM_ADMIN,
        );

        // DEMO_SEED=false でも非本番ではドキュメント通り demo_sys でログインできるようにする
        if ($useDemo === false && app()->environment('production') === false) {
            self::upsertDocumentedDemoSystemAdmin();
        }

        $this->ensureDefaultProject();
    }

    public function ensureDevelopmentAdminsExist(): void
    {
        if (app()->environment('production')) {
            return;
        }

        $this->ensureDefaultProject();
        self::upsertDocumentedDemoSystemAdmin();
        if (self::isDemoSeedEnabled()) {
            app(DemoAdminUsersSeeder::class)->run();
        }
    }

    private function ensureDefaultProject(): Project
    {
        $project = Project::where('project_key', 'default')->first();
        if ($project !== null) {
            if ($project->is_active === false) {
                $project->update(['is_active' => true]);
            }

            return $project;
        }

        return Project::create([
            'project_key' => 'default',
            'name' => 'デフォルト店舗',
            'description' => 'デモ用プロジェクト',
            'is_active' => true,
        ]);
    }

    /** ドキュメント記載のデモ用システム管理者（既定: demo_sys）を DB に反映する */
    public static function upsertDocumentedDemoSystemAdmin(): void
    {
        $loginId = env('DEMO_SYSTEM_LOGIN_ID', 'demo_sys');
        self::upsertAdminWithoutResettingPassword(
            $loginId,
            env('DEMO_SYSTEM_EMAIL', $loginId.'@example.com'),
            self::demoSystemPassword(),
            env('DEMO_SYSTEM_NAME', 'デモシステム管理者'),
            AdminUser::ROLE_SYSTEM_ADMIN,
        );
    }
}
