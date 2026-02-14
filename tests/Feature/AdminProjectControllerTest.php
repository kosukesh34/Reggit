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

    public function testIndexShowsProjects(): void
    {
        $this->loginSystemAdmin();

        $response = $this->get('/system-admin/projects');

        $response->assertStatus(200);
    }

    public function testStoreCreatesProject(): void
    {
        $this->loginSystemAdmin();

        $response = $this->post('/system-admin/projects', [
            'project_key' => 'proj-test',
            'name' => 'Test Project',
            'description' => 'Desc',
            'is_active' => true,
        ]);

        $response->assertRedirect('/system-admin/projects');
        $this->assertDatabaseHas('projects', ['project_key' => 'proj-test']);
    }

    public function testUpdateUpdatesProject(): void
    {
        $this->loginSystemAdmin();
        $project = Project::factory()->create(['name' => 'Old']);

        $response = $this->put('/system-admin/projects/' . $project->id, [
            'project_key' => $project->project_key,
            'name' => 'New',
            'description' => 'Updated',
            'is_active' => true,
        ]);

        $response->assertRedirect('/system-admin/projects');
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'New']);
    }
}
