<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    private function loginSystemAdmin(): AdminUser
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_index_プロジェクト一覧が表示されること(): void
    {
        $this->loginSystemAdmin();

        $response = $this->get('/system-admin/projects');

        $response->assertStatus(200);
    }

    public function test_store_プロジェクトが作成されること(): void
    {
        $this->loginSystemAdmin();

        $response = $this->post('/system-admin/projects', [
            'project_key' => 'proj-test',
            'name' => 'Test Project',
            'description' => 'Desc',
            'is_active' => true,
            'plan' => 'normal',
        ]);

        $response->assertRedirect(route('system-admin.projects.index'));
        $this->assertDatabaseHas('projects', ['project_key' => 'proj-test']);
    }

    public function test_update_プロジェクトが更新されること(): void
    {
        $this->loginSystemAdmin();
        $project = Project::factory()->create(['name' => 'Old']);

        $response = $this->put('/system-admin/projects/' . $project->id, [
            'project_key' => $project->project_key,
            'name' => 'New',
            'description' => 'Updated',
            'is_active' => true,
            'plan' => 'premium',
        ]);

        $response->assertRedirect(route('system-admin.projects.index'));
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'New']);
    }
}
