<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_dashboard_プロジェクト管理者がダッシュボードにアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_dashboard_ダッシュボードにコンテンツが表示されること(): void
    {
        $project = $this->createProject();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.dashboard', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('ダッシュボード', false);
    }
}
