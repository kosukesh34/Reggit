<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Database\Seeder;

class DemoAdminUsersSeeder extends Seeder
{
    public function run(): void
    {
        if (! filter_var(env('DEMO_SEED', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        if (app()->environment('production')) {
            throw new \RuntimeException('Demo administrators cannot be seeded in production.');
        }

        $project = Project::where('is_active', true)->orderBy('id')->first();
        if ($project === null) {
            $project = Project::create([
                'project_key' => 'default',
                'name' => 'デモ店舗',
                'description' => 'デモ用プロジェクト',
                'is_active' => true,
            ]);
        }

        $this->upsertDemoUser(
            env('DEMO_SYSTEM_LOGIN_ID', 'demo_sys'),
            env('DEMO_SYSTEM_EMAIL', 'demo_sys@example.com'),
            AdminUserSeeder::demoSystemPassword(),
            env('DEMO_SYSTEM_NAME', 'デモシステム管理者'),
            AdminUser::ROLE_SYSTEM_ADMIN,
            null
        );

        $projectAdmin = $this->upsertDemoUser(
            env('DEMO_PROJECT_LOGIN_ID', 'demo_proj'),
            env('DEMO_PROJECT_EMAIL', 'demo_proj@example.com'),
            env('DEMO_PROJECT_PASSWORD', 'DemoProj#2026'),
            env('DEMO_PROJECT_NAME', 'デモプロジェクト管理者'),
            AdminUser::ROLE_PROJECT_ADMIN,
            $project->id
        );
        if ($projectAdmin->projects()->count() === 0) {
            $projectAdmin->projects()->sync([$project->id]);
        }

        $storeAdmin = $this->upsertDemoUser(
            env('DEMO_STORE_LOGIN_ID', 'demo_store'),
            env('DEMO_STORE_EMAIL', 'demo_store@example.com'),
            env('DEMO_STORE_PASSWORD', 'DemoStore#2026'),
            env('DEMO_STORE_NAME', 'デモ店舗管理者'),
            AdminUser::ROLE_ADMIN,
            $project->id
        );
        if ($storeAdmin->projects()->count() === 0) {
            $storeAdmin->projects()->sync([$project->id]);
        }

        // スタッフデモユーザー
        $staff = $this->upsertDemoUser(
            env('DEMO_STAFF_LOGIN_ID', 'demo_staff'),
            env('DEMO_STAFF_EMAIL', 'demo_staff@example.com'),
            env('DEMO_STAFF_PASSWORD', 'DemoStaff#2026'),
            env('DEMO_STAFF_NAME', 'デモスタッフ'),
            AdminUser::ROLE_STAFF,
            $project->id
        );
        if ($staff->projects()->count() === 0) {
            $staff->projects()->sync([$project->id]);
        }
        // スタッフに基本的な閲覧+注文処理権限を付与
        $staffPermissions = [
            'dashboard.view',
            'orders.view',
            'orders.update',
            'products.view',
            'queue.view',
            'queue.update',
        ];
        if ($staff->permissions()->count() === 0) {
            foreach ($staffPermissions as $perm) {
                $staff->permissions()->firstOrCreate(['permission' => $perm]);
            }
        }
    }

    private function upsertDemoUser(
        string $loginId,
        string $email,
        string $password,
        string $name,
        string $role,
        ?int $projectId
    ): AdminUser {
        $user = AdminUser::firstOrCreate(
            ['login_id' => $loginId],
            [
                'email' => $email,
                'password' => $password, // 'hashed' cast でハッシュ化される
                'name' => $name,
                'role' => $role,
                'project_id' => $projectId,
            ]
        );

        return $user;
    }
}
