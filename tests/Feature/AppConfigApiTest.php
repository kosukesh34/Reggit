<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppConfigApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_config_有効なプロジェクトキーで設定を返すこと(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'テスト店舗', 'description' => 'Test', 'is_active' => true]
        );
        $project->update(['name' => 'テスト店舗', 'is_active' => true]);

        $response = $this->getJson('/api/v1/app-config?project_key=default');

        $response->assertOk();
        $response->assertJsonPath('project_key', 'default');
        $response->assertJsonPath('project_name', 'テスト店舗');
        $response->assertJsonStructure([
            'project_key',
            'project_name',
            'tabs',
            'navigation' => ['header', 'footer', 'hamburger'],
            'feature_flags',
            'theme',
            'header',
            'line' => [
                'liff_id',
                'liff_url',
                'friend_add_url',
            ],
            'liff_tab_layouts',
            'liff_tab_settings',
            'liff_detail_layouts',
        ]);
    }

    public function test_app_config_の_globalに_require_membershipが含まれること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'テスト店舗', 'description' => 'Test', 'is_active' => true]
        );
        $project->update(['name' => 'テスト店舗', 'is_active' => true]);

        $response = $this->getJson('/api/v1/app-config?project_key=default');

        $response->assertOk();
        $settings = $response->json('liff_tab_settings');
        $this->assertIsArray($settings);
        $this->assertArrayHasKey('_global', $settings);
        $global = $settings['_global'];
        $this->assertIsArray($global);
        $this->assertArrayHasKey('require_membership', $global);
        $this->assertTrue((bool) $global['require_membership']);
    }

    public function test_app_config_のdetail_layoutsに深い遷移キーが含まれること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'テスト店舗', 'description' => 'Test', 'is_active' => true]
        );
        $project->update(['name' => 'テスト店舗', 'is_active' => true]);

        $response = $this->getJson('/api/v1/app-config?project_key=default');

        $response->assertOk();
        $layouts = $response->json('liff_detail_layouts');
        $this->assertIsArray($layouts);
        foreach ([
            'announcement_detail',
            'product_detail',
            'coupon_detail',
            'gallery_detail',
            'stamp_detail',
            'order_cart',
            'order_confirm',
            'order_complete',
            'reservation_new',
            'reservation_confirm',
            'reservation_complete',
        ] as $key) {
            $this->assertArrayHasKey($key, $layouts);
            $this->assertIsArray($layouts[$key]);
        }
    }

    public function test_app_config_project_key未指定は400を返すこと(): void
    {
        $response = $this->getJson('/api/v1/app-config');

        $response->assertStatus(400);
        $response->assertJsonPath('error', 'project_key は必須です。');
    }

    public function test_app_config_ナビゲーションバー画像を返すこと(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'テスト店舗', 'description' => 'Test', 'is_active' => true]
        );
        ShopSettingStore::setValue(
            'navigation_header_image_url',
            'https://example.com/store-header.webp',
            'string',
            $project->id,
        );
        ShopSettingStore::setValue('home_menu_layout', 'grid_2_columns', 'string', $project->id);
        ShopSettingStore::setValue('product_display_layout', 'grid_2_columns', 'string', $project->id);
        ShopSettingStore::setValue('navigation_header_background_color', '#F6F1E8', 'string', $project->id);
        ShopSettingStore::setValue('home_image_url', 'https://example.com/home.webp', 'string', $project->id);
        ShopSettingStore::setValue('member_show_point_history', false, 'boolean', $project->id);
        ShopSettingStore::setValue('native_navigation_items', [
            'header' => ['member'],
            'footer' => ['home', 'products'],
            'hamburger' => ['orders', 'terms', 'privacy'],
        ], 'json', $project->id);

        $this->getJson('/api/v1/app-config?project_key=default')
            ->assertOk()
            ->assertJsonPath('header.image_url', 'https://example.com/store-header.webp')
            ->assertJsonPath('header.background_color', '#F6F1E8')
            ->assertJsonPath('home.image_url', 'https://example.com/home.webp')
            ->assertJsonPath('home.menu_layout', 'grid_2_columns')
            ->assertJsonPath('products.display_layout', 'grid_2_columns')
            ->assertJsonPath('navigation.header.0.key', 'member')
            ->assertJsonPath('navigation.footer.1.key', 'products')
            ->assertJsonPath('navigation.hamburger.1.key', 'terms')
            ->assertJsonPath('navigation.hamburger.2.key', 'privacy')
            ->assertJsonPath('navigation.hamburger.1.url', route('legal.terms', ['project_key' => 'default']))
            ->assertJsonPath('navigation.hamburger.2.url', route('legal.privacy', ['project_key' => 'default']))
            ->assertJsonPath('member_screen.show_point_history', false);
    }

    public function test_app_config_抽選機能が有効な場合にあそびメニューを返すこと(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'テスト店舗', 'description' => 'Test', 'is_active' => true]
        );
        ShopSettingStore::setValue('feature_gacha_enabled', true, 'boolean', $project->id);

        $response = $this->getJson('/api/v1/app-config?project_key=default')->assertOk();

        $this->assertContains('designlab', collect($response->json('tabs'))->pluck('key')->all());
    }
}
