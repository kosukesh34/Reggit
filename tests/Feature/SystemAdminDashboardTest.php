<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemAdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_システム管理者用ダッシュボードにアクセスできること(): void
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('system-admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('システム管理', false);
        $response->assertSee('プロジェクト', false);
        $response->assertSee('アカウント', false);
        $response->assertDontSee('共通設定', false);
        $response->assertDontSee('APIトークン', false);
        $response->assertDontSee('店舗アプリUI学習', false);
    }

    public function test_system_admin_root_redirects_to_dashboard(): void
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);
        $this->actingAs($admin, 'admin')->get('/system-admin')
            ->assertRedirect(route('system-admin.dashboard'));
    }

    public function test_dashboard_廃止した画面は404になること(): void
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);

        $this->actingAs($admin, 'admin')
            ->get('/system-admin/api-tokens')
            ->assertNotFound();
        $this->actingAs($admin, 'admin')
            ->get('/system-admin/shop-app-ui-research')
            ->assertNotFound();
    }
}
