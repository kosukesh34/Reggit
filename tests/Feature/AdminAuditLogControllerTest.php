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

        $response->assertStatus(200)->assertSee('管理者')->assertSee('対象')->assertSee('変更項目');
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

        $response = $this->get('/system-admin/audit-logs/'.$log->id);

        $response->assertStatus(200)->assertSee('変更項目')->assertDontSee('Payload')->assertDontSee('User-Agent');
    }

    public function test_audit_logger_個人情報と自由入力値を保存しない(): void
    {
        $admin = $this->loginSystemAdmin();
        $this->post('/system-admin/projects', [
            'name' => '公開用名称',
            'email' => 'private@example.com',
            'phone' => '09012345678',
            'password' => 'secret-password',
            'service_status' => 'active',
        ]);

        $log = AdminAuditLog::query()->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame(['service_status'], array_keys($log->payload));
        $this->assertNull($log->ip);
        $this->assertNull($log->user_agent);
        $this->assertStringNotContainsString('private@example.com', $log->url);
    }
}
