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

    public function testSystemAdminLoginPageRedirectsToAdminLogin(): void
    {
        $response = $this->get(route('system-admin.login'));

        $response->assertStatus(200);
        $response->assertSee('システム管理画面');
    }

    public function testStoreAdminLoginRedirectsToDashboardWhenValid(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
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

    public function testStoreAdminLoginRejectsSystemAdminAccount(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        AdminUser::factory()->create([
            'login_id' => 'sys_for_admin_login',
            'password' => 'password',
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->from(route('admin.login.form'))->post(route('admin.login.submit'), [
            'login_id' => 'sys_for_admin_login',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.login.form'));
        $response->assertSessionHasErrors('login_id');
        $this->assertGuest('admin');
    }

    public function testSystemAdminLoginRedirectsWhenValid(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        AdminUser::factory()->create([
            'login_id' => 'sys_admin',
            'password' => 'password',
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->post(route('system-admin.login.submit'), [
            'login_id' => 'sys_admin',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('system-admin.projects.index'));
        $this->assertAuthenticated('admin');
    }

    public function testSystemAdminLoginRejectsNonSystemAdminAccount(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        AdminUser::factory()->create([
            'login_id' => 'normal_admin',
            'password' => 'password',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->from(route('system-admin.login'))->post(route('system-admin.login.submit'), [
            'login_id' => 'normal_admin',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('system-admin.login'));
        $response->assertSessionHasErrors('login_id');
        $this->assertGuest('admin');
    }

    public function testUnauthenticatedUserRedirectedFromAdminRoot(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('admin.login'));
    }

    public function testSystemAdminCanAccessAdminRootAndRedirectedToProjectDashboard(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $sys = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($sys, 'admin')->get('/admin');

        $response->assertRedirect(route('admin.dashboard', ['project_key' => $project->project_key]));
    }

    public function testSystemAdminLoginPageRedirectsNonSystemAdminSessionToAdminArea(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($admin, 'admin')->get(route('system-admin.login'));

        $response->assertRedirect(route('admin.dashboard', ['project_key' => $project->project_key]));
        $this->assertAuthenticated('admin');
    }
}
