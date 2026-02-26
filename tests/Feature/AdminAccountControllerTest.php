<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccountControllerTest extends TestCase
{
    use RefreshDatabase;

    private function loginSystemAdmin(): AdminUser
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_index_アカウント一覧が表示されること(): void
    {
        $this->loginSystemAdmin();

        $response = $this->get('/system-admin/accounts');

        $response->assertStatus(200);
    }

    public function test_create_作成フォームが表示されること(): void
    {
        $this->loginSystemAdmin();

        $response = $this->get('/system-admin/accounts/create');

        $response->assertStatus(200);
    }

    public function test_store_アカウントが作成されること(): void
    {
        $this->loginSystemAdmin();
        $project = Project::factory()->create();

        $response = $this->post('/system-admin/accounts', [
            'login_id' => 'test_admin',
            'email' => 'admin@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'name' => 'Admin User',
            'role' => AdminUser::ROLE_ADMIN,
            'project_ids' => [$project->id],
        ]);

        $response->assertRedirect('/system-admin/accounts');
        $this->assertDatabaseHas('admin_users', ['login_id' => 'test_admin']);
    }

    public function test_store_システム管理者作成時は全プロジェクトが割り当てられること(): void
    {
        $this->loginSystemAdmin();
        $projectA = Project::factory()->create(['is_active' => true]);
        $projectB = Project::factory()->create(['is_active' => true]);

        $response = $this->post('/system-admin/accounts', [
            'login_id' => 'sys_all_projects',
            'email' => 'sys_all_projects@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'name' => 'System All',
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
        ]);

        $response->assertRedirect('/system-admin/accounts');
        $created = AdminUser::where('login_id', 'sys_all_projects')->firstOrFail();
        $this->assertSame(AdminUser::ROLE_SYSTEM_ADMIN, $created->role);
        $this->assertTrue($created->projects()->where('projects.id', $projectA->id)->exists());
        $this->assertTrue($created->projects()->where('projects.id', $projectB->id)->exists());
    }

    public function test_update_アカウントが更新されること(): void
    {
        $this->loginSystemAdmin();
        $project = Project::factory()->create();
        $target = AdminUser::factory()->create([
            'login_id' => 'target_admin',
            'email' => 'target@example.com',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->put('/system-admin/accounts/' . $target->id, [
            'login_id' => $target->login_id,
            'email' => $target->email,
            'name' => 'Updated',
            'role' => AdminUser::ROLE_ADMIN,
            'project_ids' => [$project->id],
        ]);

        $response->assertRedirect('/system-admin/accounts');
        $this->assertDatabaseHas('admin_users', ['id' => $target->id, 'name' => 'Updated']);
    }

    public function test_destroy_アカウントが削除されること(): void
    {
        $this->loginSystemAdmin();
        $target = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN]);

        $response = $this->delete('/system-admin/accounts/' . $target->id);

        $response->assertRedirect('/system-admin/accounts');
        $this->assertDatabaseMissing('admin_users', ['id' => $target->id]);
    }

    public function test_update_システム管理者更新時は全プロジェクトが割り当てられること(): void
    {
        $this->loginSystemAdmin();
        $projectA = Project::factory()->create(['is_active' => true]);
        $projectB = Project::factory()->create(['is_active' => true]);
        $target = AdminUser::factory()->create([
            'login_id' => 'target_sys',
            'email' => 'target_sys@example.com',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $projectA->id,
        ]);

        $response = $this->put('/system-admin/accounts/' . $target->id, [
            'login_id' => $target->login_id,
            'email' => $target->email,
            'name' => 'System Updated',
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
        ]);

        $response->assertRedirect('/system-admin/accounts');
        $target->refresh();
        $this->assertSame(AdminUser::ROLE_SYSTEM_ADMIN, $target->role);
        $this->assertTrue($target->projects()->where('projects.id', $projectA->id)->exists());
        $this->assertTrue($target->projects()->where('projects.id', $projectB->id)->exists());
    }
}
