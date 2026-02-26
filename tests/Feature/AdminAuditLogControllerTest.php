<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuditLogControllerTest extends TestCase
{
    use RefreshDatabase;

    private function loginSystemAdmin(): AdminUser
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $this->actingAs($admin, 'admin');
        return $admin;
    }

    public function test_index_監査ログ一覧が表示されること(): void
    {
        $admin = $this->loginSystemAdmin();
        AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'project_id' => null,
            'route_name' => 'system-admin.projects.index',
            'method' => 'GET',
            'url' => '/system-admin/projects',
            'ip' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => ['event' => 'test'],
        ]);

        $response = $this->get('/system-admin/audit-logs');

        $response->assertStatus(200);
    }

    public function test_show_監査ログ詳細が表示されること(): void
    {
        $admin = $this->loginSystemAdmin();
        $log = AdminAuditLog::create([
            'admin_user_id' => $admin->id,
            'project_id' => null,
            'route_name' => 'system-admin.projects.index',
            'method' => 'GET',
            'url' => '/system-admin/projects',
            'ip' => '127.0.0.1',
            'user_agent' => 'test',
            'payload' => ['event' => 'test'],
        ]);

        $response = $this->get('/system-admin/audit-logs/' . $log->id);

        $response->assertStatus(200);
    }
}
