<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $loginId = env('ADMIN_EMAIL', 'reserve');
        $password = env('ADMIN_PASSWORD', 'reserve');
        $name = env('ADMIN_NAME', 'システムアドミン');

        if (AdminUser::where('login_id', $loginId)->exists()) {
            return;
        }

        AdminUser::create([
            'login_id' => $loginId,
            'email' => $loginId . '@system.local',
            'password' => $password,
            'name' => $name,
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
            'project_id' => null,
        ]);

        // 初回のみデフォルトプロジェクトを作成（DemoAdminUsersSeeder で使う）
        if (!Project::where('is_active', true)->exists()) {
            Project::create([
                'project_key' => 'default',
                'name' => 'デフォルト店舗',
                'description' => 'デモ用プロジェクト',
                'is_active' => true,
            ]);
        }
    }
}
