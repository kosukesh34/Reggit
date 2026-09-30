<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppConfigApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_ギャラリーはホームメニューで非表示にするとアプリ設定から消えること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'テスト店舗', 'description' => 'Test', 'is_active' => true]
        );
        ShopSettingStore::setFrontendMenuItems([
            ['tab' => 'gallery', 'label' => 'ギャラリー', 'enabled' => false, 'order' => 10],
        ], $project->id);

        $response = $this->getJson('/api/v1/app-config?project_key=default');

        $response->assertOk()->assertJsonPath('feature_flags.feature_gallery_enabled', false);
        $this->assertNotContains('gallery', array_column($response->json('tabs'), 'key'));
    }

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
            ->assertJsonPath('home.image_urls', ['https://example.com/home.webp'])
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

    public function test_web_page_open_mode_is_returned_for_all_native_placements(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'テスト店舗', 'is_active' => true]
        );
        ShopSettingStore::setFrontendMenuItems([
            ['tab' => 'link_1', 'label' => '外部サイト', 'url' => 'https://example.com', 'open' => 'external', 'enabled' => true, 'order' => 0],
            ['tab' => 'link_2', 'label' => '内部サイト', 'url' => 'https://example.com/internal', 'open' => 'embed', 'enabled' => true, 'order' => 1],
        ], $project->id);
        ShopSettingStore::setValue('native_navigation_items', [
            'header' => ['link_1'],
            'footer' => ['link_1', 'link_2'],
            'hamburger' => ['link_1', 'link_2'],
        ], 'json', $project->id);

        $response = $this->getJson('/api/v1/app-config?project_key=default')->assertOk();
        $this->assertSame('external', collect($response->json('tabs'))->firstWhere('key', 'link_1')['open']);
        $this->assertSame('embed', collect($response->json('tabs'))->firstWhere('key', 'link_2')['open']);
        foreach (['header', 'footer', 'hamburger'] as $placement) {
            $response->assertJsonPath("navigation.{$placement}.0.open", 'external')
                ->assertJsonPath("navigation.{$placement}.0.url", 'https://example.com');
        }
        $response->assertJsonPath('navigation.footer.1.open', 'embed');
    }

    public function test_web_page_hidden_from_home_can_still_be_used_in_all_navigation_placements(): void
    {
        $project = Project::firstOrCreate(['project_key' => 'default'], ['name' => 'テスト店舗', 'is_active' => true]);
        ShopSettingStore::setFrontendMenuItems([
            ['tab' => 'link_1', 'label' => '公式サイト', 'url' => 'https://example.com', 'open' => 'external', 'enabled' => false, 'order' => 0],
        ], $project->id);
        ShopSettingStore::setValue('native_navigation_items', [
            'header' => ['link_1'], 'footer' => ['link_1'], 'hamburger' => ['link_1'],
        ], 'json', $project->id);

        $response = $this->getJson('/api/v1/app-config?project_key=default')->assertOk();
        $this->assertNull(collect($response->json('tabs'))->firstWhere('key', 'link_1'));
        foreach (['header', 'footer', 'hamburger'] as $placement) {
            $response->assertJsonPath("navigation.{$placement}.0.key", 'link_1')
                ->assertJsonPath("navigation.{$placement}.0.open", 'external')
                ->assertJsonPath("navigation.{$placement}.0.url", 'https://example.com');
        }
    }

    public function test_app_config_フッターの明示設定はホームメニュー非表示でも反映されること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'テスト店舗', 'description' => 'Test', 'is_active' => true]
        );
        ShopSettingStore::setFrontendMenuItems([
            ['tab' => 'home', 'label' => 'ホーム', 'enabled' => true, 'order' => 0],
            ['tab' => 'member', 'label' => '会員証', 'enabled' => false, 'order' => 1],
            ['tab' => 'coupons', 'label' => 'クーポン', 'enabled' => false, 'order' => 2],
        ], $project->id);
        ShopSettingStore::setValue('native_navigation_items', [
            'header' => ['member', 'coupons'],
            'footer' => ['home', 'member', 'coupons'],
            'hamburger' => [],
        ], 'json', $project->id);

        $response = $this->getJson('/api/v1/app-config?project_key=default')->assertOk();

        $this->assertSame(['home', 'member', 'coupons'], array_column($response->json('navigation.footer'), 'key'));
        $this->assertSame(['member'], array_column($response->json('navigation.header'), 'key'));
    }

    public function test_app_config_複数のホーム画像を登録順に返すこと(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'テスト店舗', 'description' => 'Test', 'is_active' => true]
        );
        ShopSettingStore::setValue('home_image_urls', [
            'https://example.com/first.webp',
            'https://example.com/second.webp',
        ], 'json', $project->id);

        $this->getJson('/api/v1/app-config?project_key=default')
            ->assertOk()
            ->assertJsonPath('home.image_url', 'https://example.com/first.webp')
            ->assertJsonPath('home.image_urls', [
                'https://example.com/first.webp',
                'https://example.com/second.webp',
            ]);
    }

    public function test_app_config_抽選機能が有効でもネイティブのあそびタブを返さないこと(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'テスト店舗', 'description' => 'Test', 'is_active' => true]
        );
        ShopSettingStore::setValue('feature_gacha_enabled', true, 'boolean', $project->id);

        $response = $this->getJson('/api/v1/app-config?project_key=default')->assertOk();

        $this->assertNotContains('designlab', collect($response->json('tabs'))->pluck('key')->all());
    }

    public function test_app_config_reverb接続先を返すこと(): void
    {
        Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'テスト店舗', 'description' => 'Test', 'is_active' => true]
        );
        config([
            'app.url' => 'https://example.com',
            'broadcasting.default' => 'reverb',
            'broadcasting.client_ws_host' => 'ws.example.com',
            'broadcasting.connections.reverb.key' => 'public-key',
        ]);

        $this->getJson('/api/v1/app-config?project_key=default')
            ->assertOk()
            ->assertJsonPath('realtime.websocket_url', 'wss://ws.example.com/app/public-key?protocol=7&client=reggit-skip&version=1.0');
    }
}
