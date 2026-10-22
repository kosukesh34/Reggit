<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Project;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMenuFeatureFlagsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    public function test_無効にした機能のメニューと設定リンクは表示しないこと(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        /** @var AdminUser $admin */
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $key = ['project_key' => $project->project_key];
        foreach (['reservations', 'queue', 'coupons', 'announcements', 'stamps', 'orders', 'shop', 'tickets'] as $feature) {
            ShopSettingStore::setValue('feature_'.$feature.'_enabled', false, 'boolean', $project->id);
        }

        $html = $this->actingAs($admin, 'admin')->get(route('admin.settings.index', $key))->assertOk()->getContent();
        foreach (['admin.reservations', 'admin.queue', 'admin.coupons', 'admin.announcements', 'admin.stamps', 'admin.orders', 'admin.products',
            'admin.settings.reservations', 'admin.settings.queue', 'admin.settings.coupons', 'admin.settings.announcements'] as $route) {
            $this->assertStringNotContainsString('href="'.route($route, $key).'"', $html, $route);
        }
        // 会員と、ショップを有効にする設定画面は常に出す
        foreach (['admin.members', 'admin.settings.shop', 'admin.settings.features'] as $route) {
            $this->assertStringContainsString('href="'.route($route, $key).'"', $html, $route);
        }

        // 回数券だけ有効なら注文と商品は出す
        ShopSettingStore::setValue('feature_tickets_enabled', true, 'boolean', $project->id);
        $html = $this->actingAs($admin, 'admin')->get(route('admin.settings.index', $key))->assertOk()->getContent();
        $this->assertStringContainsString('href="'.route('admin.orders', $key).'"', $html);
        $this->assertStringContainsString('href="'.route('admin.products', $key).'"', $html);
        $this->assertStringNotContainsString('href="'.route('admin.reservations', $key).'"', $html);
    }
}
