<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopAppUiResearchPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_システム管理者は学習データページを閲覧できる(): void
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('system-admin.shop-app-ui-research'));

        $response->assertStatus(200);
        $response->assertSee('店舗アプリUI学習データ', false);
        $response->assertSee('学習原則', false);
        $response->assertSee('Starbucks Rewards', false);
    }

    public function test_代表スクショ資産を配信できる(): void
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_SYSTEM_ADMIN]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('system-admin.shop-app-ui-research.asset', [
                'path' => 'app-ui-screenshots/_featured/01-starbucks-rewards.jpg',
            ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'image/jpeg');
    }

    public function test_一般管理者は学習データページにアクセスできない(): void
    {
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('system-admin.shop-app-ui-research'));

        $response->assertStatus(403);
    }
}
