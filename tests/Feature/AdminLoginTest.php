<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function testStoreAdminLoginPageIsAccessible(): void
    {
        $response = $this->get(route('admin.login'));

        $response->assertStatus(200);
    }

    public function testSystemAdminLoginPageIsAccessible(): void
    {
        $response = $this->get(route('system-admin.login'));

        $response->assertStatus(200);
    }

    public function testStoreAdminLoginRedirectsToDashboardWhenValid(): void
    {
        $project = Project::factory()->create(['project_key' => 'default', 'is_active' => true]);
        AdminUser::factory()->create([
            'login_id' => 'store_admin',
            'password' => 'password',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->post(route('admin.login.submit'), [
            'login_id' => 'store_admin',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated('admin');
    }

    public function testSystemAdminLoginRedirectsWhenValid(): void
    {
        AdminUser::factory()->create([
            'login_id' => 'sys_admin',
            'password' => 'password',
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
        ]);

        $response = $this->post(route('system-admin.login.submit'), [
            'login_id' => 'sys_admin',
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated('admin');
    }

    public function testUnauthenticatedUserRedirectedFromAdminRoot(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('admin.login'));
    }
}
