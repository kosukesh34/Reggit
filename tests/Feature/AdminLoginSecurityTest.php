<?php

namespace Tests\Feature;

use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\Project;
use App\Notifications\AdminLoginAlert;
use App\Support\AdminPasskeyGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminLoginSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_許可されていないipからのログインが拒否されること(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        AdminUser::factory()->create([
            'login_id' => 'store_admin',
            'password' => 'password',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
            'login_allowed_ips' => '203.0.113.10',
        ]);

        $this->post(route('admin.login.submit'), [
            'login_id' => 'store_admin',
            'password' => 'password',
        ], ['REMOTE_ADDR' => '198.51.100.20'])
            ->assertSessionHasErrors('login_id');

        $this->assertGuest('admin');
    }

    public function test_login_システム管理者はip制限とパスキーをバイパスすること(): void
    {
        AdminUser::factory()->create([
            'login_id' => 'sys_admin',
            'password' => 'password',
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
            'project_id' => null,
            'login_allowed_ips' => '203.0.113.10',
        ]);

        $this->post(route('system-admin.login.submit'), [
            'login_id' => 'sys_admin',
            'password' => 'password',
        ], ['REMOTE_ADDR' => '198.51.100.20'])
            ->assertRedirect(route('system-admin.projects.index'));

        $this->assertAuthenticated('admin');
        $this->assertTrue((bool) session(AdminPasskeyGate::PASSKEY_VERIFIED));
    }

    public function test_admin_パスキー確認済みのシステム管理者はプロジェクトへ追加認証なしで入れること(): void
    {
        $alpha = Project::factory()->create(['is_active' => true, 'project_key' => 'alpha']);
        $beta = Project::factory()->create(['is_active' => true, 'project_key' => 'beta']);
        $admin = AdminUser::factory()->create([
            'login_id' => 'sys_admin',
            'password' => 'password',
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
            'project_id' => null,
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession([
                AdminPasskeyGate::PASSWORD_AUTHENTICATED => true,
                AdminPasskeyGate::PASSKEY_VERIFIED => true,
            ])
            ->get(route('admin.dashboard', ['project_key' => $alpha->project_key]))
            ->assertOk();

        $this->get(route('admin.dashboard', ['project_key' => $beta->project_key]))
            ->assertOk();
    }

    public function test_login_システム管理者の監査はシステム管理ログインのみ残ること(): void
    {
        $admin = AdminUser::factory()->create([
            'login_id' => 'sys_admin',
            'password' => 'password',
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
            'project_id' => null,
        ]);
        $project = Project::factory()->create(['is_active' => true]);

        $this->post(route('admin.project.login.submit', ['project_key' => $project->project_key]), [
            'login_id' => 'sys_admin',
            'password' => 'password',
        ]);
        $this->assertSame(0, AdminAuditLog::query()->where('payload->event', 'login_success')->count());

        $this->post(route('admin.logout'));
        $this->assertSame(0, AdminAuditLog::query()->where('payload->event', 'logout')->count());

        $this->post(route('system-admin.login.submit'), [
            'login_id' => 'sys_admin',
            'password' => 'password',
        ])->assertRedirect(route('system-admin.projects.index'));

        $this->assertSame(1, AdminAuditLog::query()
            ->where('admin_user_id', $admin->id)
            ->where('payload->event', 'login_success')
            ->count());
    }

    public function test_login_許可ipからのログインは成功し通知はデフォルト送信しないこと(): void
    {
        Notification::fake();
        $project = Project::factory()->create(['is_active' => true]);
        $admin = AdminUser::factory()->create([
            'login_id' => 'store_admin',
            'password' => 'password',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
            'email' => 'store@example.test',
            'login_allowed_ips' => "127.0.0.1\n::1",
            'login_notify_by_email' => false,
        ]);

        $this->post(route('admin.login.submit'), [
            'login_id' => 'store_admin',
            'password' => 'password',
        ])->assertRedirect(route('admin.passkey.enroll'));

        $this->assertAuthenticatedAs($admin, 'admin');
        Notification::assertNothingSent();
    }

    public function test_login_通知有効時はログインアラートメールが送られること(): void
    {
        Notification::fake();
        $project = Project::factory()->create(['is_active' => true]);
        $admin = AdminUser::factory()->create([
            'login_id' => 'store_admin',
            'password' => 'password',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
            'email' => 'store@example.test',
            'login_notify_by_email' => true,
        ]);

        $this->post(route('admin.login.submit'), [
            'login_id' => 'store_admin',
            'password' => 'password',
        ])->assertRedirect(route('admin.passkey.enroll'));

        Notification::assertSentTo($admin, AdminLoginAlert::class);
    }

    public function test_update_ログインセキュリティ設定を保存できること(): void
    {
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
            'project_id' => null,
            'login_notify_by_email' => false,
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession([
                AdminPasskeyGate::PASSWORD_AUTHENTICATED => true,
                AdminPasskeyGate::PASSKEY_VERIFIED => true,
            ])
            ->put(route('admin.login-security.update'), [
                'login_allowed_ips' => ['203.0.113.10', '', '198.51.100.0/24'],
                'login_notify_by_email' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $admin->refresh();
        $this->assertSame("203.0.113.10\n198.51.100.0/24", $admin->login_allowed_ips);
        $this->assertTrue($admin->login_notify_by_email);

        $this->actingAs($admin, 'admin')
            ->withSession([
                AdminPasskeyGate::PASSWORD_AUTHENTICATED => true,
                AdminPasskeyGate::PASSKEY_VERIFIED => true,
            ])
            ->put(route('admin.login-security.update'), [
                'login_allowed_ips' => [],
                'login_notify_by_email' => '0',
            ])
            ->assertRedirect();

        $admin->refresh();
        $this->assertNull($admin->login_allowed_ips);
        $this->assertFalse($admin->login_notify_by_email);
    }

    public function test_update_不正なip入力が拒否されること(): void
    {
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
            'project_id' => null,
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession([
                AdminPasskeyGate::PASSWORD_AUTHENTICATED => true,
                AdminPasskeyGate::PASSKEY_VERIFIED => true,
            ])
            ->put(route('admin.login-security.update'), [
                'login_allowed_ips' => ['not-an-ip'],
                'login_notify_by_email' => '0',
            ])
            ->assertSessionHasErrors('login_allowed_ips');
    }

    public function test_current_ip_現在の接続ipを返すこと(): void
    {
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_SYSTEM_ADMIN,
            'project_id' => null,
        ]);

        $this->actingAs($admin, 'admin')
            ->withSession([
                AdminPasskeyGate::PASSWORD_AUTHENTICATED => true,
                AdminPasskeyGate::PASSKEY_VERIFIED => true,
            ])
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.50'])
            ->getJson(route('admin.login-security.current-ip'))
            ->assertOk()
            ->assertJson(['ip' => '203.0.113.50']);
    }
}
