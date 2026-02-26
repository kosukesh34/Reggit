<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_店舗管理者ログイン画面にアクセスできること(): void
    {
        $response = $this->get(route('admin.login'));

        $response->assertStatus(200);
    }

    public function test_login_システム管理者ログイン画面が表示されること(): void
    {
        $response = $this->get(route('system-admin.login'));

        $response->assertStatus(200);
        $response->assertSee('システム管理画面');
    }

    public function test_login_店舗管理者が正しい認証でダッシュボードにリダイレクトされること(): void
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

    public function test_login_店舗管理者ログインでシステム管理者アカウントは拒否されること(): void
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

    /** システム管理者はプロジェクト指定ログイン（/admin/{project_key}/login）でログインでき、そのプロジェクトのダッシュボードへリダイレクトされる */
    public function test_login_システム管理者がプロジェクトログイン経由でログインできること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        AdminUser::factory()->create([
            'login_id' => 'sys_admin',
            'password' => 'password',
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
            'project_id' => null,
        ]);

        $response = $this->post(route('admin.project.login.submit', ['project_key' => 'default']), [
            'login_id' => 'sys_admin',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('admin.dashboard', ['project_key' => 'default']));
        $this->assertAuthenticated('admin');
    }

    public function test_login_システム管理者が正しい認証でリダイレクトされること(): void
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

    public function test_login_システム管理者ログインで一般管理者は拒否されること(): void
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

    public function test_admin_未認証ユーザーは管理画面ルートからリダイレクトされること(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_システム管理者が管理ルートにアクセスしてプロジェクトダッシュボードにリダイレクトされること(): void
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

    public function test_login_システム管理者ログイン画面で一般管理者セッションは管理エリアにリダイレクトされること(): void
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
