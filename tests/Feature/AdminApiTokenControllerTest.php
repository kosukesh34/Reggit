<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\ApiToken;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiTokenControllerTest extends TestCase
{
    use RefreshDatabase;

    private function loginSystemAdmin(): AdminUser
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_index_APIトークン一覧が表示されること(): void
    {
        $this->loginSystemAdmin();

        $response = $this->get('/system-admin/api-tokens');

        $response->assertStatus(200);
    }

    public function test_create_トークン作成フォームが表示されること(): void
    {
        $this->loginSystemAdmin();

        $response = $this->get('/system-admin/api-tokens/create');

        $response->assertStatus(200);
    }

    public function test_store_トークンが作成されること(): void
    {
        $this->loginSystemAdmin();
        $project = Project::factory()->create();

        $response = $this->post('/system-admin/api-tokens', [
            'project_id' => $project->id,
            'name' => 'Test Token',
        ]);

        $response->assertRedirect('/system-admin/api-tokens');
        $this->assertDatabaseHas('api_tokens', ['project_id' => $project->id, 'name' => 'Test Token']);
    }

    public function test_revoke_トークンが無効化されること(): void
    {
        $this->loginSystemAdmin();
        $project = Project::factory()->create();
        $issued = ApiToken::issue($project, 'Test');

        $response = $this->post('/system-admin/api-tokens/' . $issued['token']->id . '/revoke');

        $response->assertRedirect('/system-admin/api-tokens');
        $this->assertDatabaseHas('api_tokens', ['id' => $issued['token']->id, 'is_active' => false]);
    }

    public function test_rotate_新しいトークンが発行されること(): void
    {
        $this->loginSystemAdmin();
        $project = Project::factory()->create();
        $issued = ApiToken::issue($project, 'Test');

        $response = $this->post('/system-admin/api-tokens/' . $issued['token']->id . '/rotate');

        $response->assertRedirect('/system-admin/api-tokens');
        $this->assertDatabaseHas('api_tokens', ['id' => $issued['token']->id, 'is_active' => false]);
        $this->assertDatabaseCount('api_tokens', 2);
    }
}
