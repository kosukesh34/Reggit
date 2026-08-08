<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Project;
use App\Support\ShopSettingLiffDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiffDesignerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Project::query()->where('project_key', 'default')->update(['plan' => 'premium']);
    }

    public function test_liff_designer_管理者が_lif_fデザイナー画面にアクセスできること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );

        $admin = AdminUser::create([
            'login_id' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'name' => 'Admin',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.liff-designer', ['project_key' => $project->project_key]));

        $response->assertOk();
        $response->assertSee('LIFFデザイン');
    }

    public function test_liff_designer_レイアウトを保存できること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );

        $admin = AdminUser::create([
            'login_id' => 'admin2',
            'email' => 'admin2@example.com',
            'password' => 'password',
            'name' => 'Admin2',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $layouts = [
            'home' => [
                ['type' => 'text', 'title' => 'Hello', 'body' => 'World', 'design' => 'lead'],
                ['type' => 'slider', 'title' => 'Slides', 'design' => 'cards', 'slider_images' => ['https://example.com/a.jpg']],
            ],
        ];

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.liff-designer.update', ['project_key' => $project->project_key]), [
                'layouts_json' => json_encode($layouts),
            ]);

        $response->assertRedirect(route('admin.settings.liff-designer', ['project_key' => $project->project_key]));

        $stored = ShopSettingStore::getLiffTabLayouts($project->id);
        $this->assertArrayHasKey('home', $stored);
        $this->assertCount(2, $stored['home']);
        $this->assertSame('text', $stored['home'][0]['type']);
        $this->assertSame('World', $stored['home'][0]['body'] ?? $stored['home'][0]['text'] ?? null);
        $this->assertSame('lead', $stored['home'][0]['design']);
        $this->assertSame('slider', $stored['home'][1]['type']);
        $this->assertSame('cards', $stored['home'][1]['design']);
        $this->assertTrue(isset($stored['home'][1]['slides']) || isset($stored['home'][1]['slider_images']));
    }

    public function test_liff_designer_新ブロックとテーマ設定とタブメニューを保存できること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );

        $admin = AdminUser::create([
            'login_id' => 'admin3',
            'email' => 'admin3@example.com',
            'password' => 'password',
            'name' => 'Admin3',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $layouts = [
            'home' => [
                ['type' => 'hours', 'title' => '営業時間', 'design' => 'timeline', 'rows' => [['day' => '平日', 'time' => '10:00 - 20:00']]],
                ['type' => 'access', 'title' => '店舗情報', 'design' => 'map', 'address' => '東京都渋谷区1-2-3', 'phone' => '03-1234-5678', 'map_url' => 'https://maps.google.com/?q=x'],
                ['type' => 'social_links', 'links' => [['type' => 'instagram', 'url' => 'https://instagram.com/shop'], ['type' => 'bad', 'url' => 'https://x.com/shop']]],
                ['type' => 'faq', 'design' => 'cards', 'items' => [['q' => '予約は必要？', 'a' => '不要です。']]],
                ['type' => 'video', 'youtube_id' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
                ['type' => 'button_group', 'buttons' => [['text' => '予約', 'action' => '#reservations', 'variant' => 'outline']]],
            ],
        ];

        $tabSettings = [
            '_global' => [
                'theme_preset' => 'matcha',
                'card_style' => 'outline',
                'button_shape' => 'square',
                'density' => 'relaxed',
                'tab_style' => 'pill',
                'background_style' => 'image',
                'bg_color' => '#f7f6f2',
                'bg_image_url' => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?w=1600',
                'bg_overlay_opacity' => 55,
                'content_width' => 'narrow',
                'system_ui_style' => 'luxe',
                'system_card_style' => 'elevated',
                'system_media_shape' => 'arch',
                'system_header_style' => 'hero',
                'member_card_design' => 'emblem',
                'member_screen_design' => 'premium',
                'coupon_screen_design' => 'gallery',
                'reservation_screen_design' => 'timeline',
                'activity_screen_design' => 'dashboard',
            ],
        ];

        $menuItems = [
            ['tab' => 'reservations', 'label' => 'ご予約', 'icon' => 'fa-calendar-check', 'enabled' => true],
            ['tab' => 'home', 'label' => '', 'icon' => '', 'enabled' => true],
            ['tab' => 'products', 'label' => 'お品書き', 'icon' => 'fa-utensils', 'enabled' => false],
            ['tab' => 'invalid-tab', 'label' => 'x', 'icon' => 'fa-x', 'enabled' => true],
        ];

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.liff-designer.update', ['project_key' => $project->project_key]), [
                'layouts_json' => json_encode($layouts),
                'tab_settings_json' => json_encode($tabSettings),
                'menu_items_json' => json_encode($menuItems),
            ]);

        $response->assertRedirect(route('admin.settings.liff-designer', ['project_key' => $project->project_key]));

        // 新ブロックが保存されている
        $stored = ShopSettingStore::getLiffTabLayouts($project->id);
        $types = array_column($stored['home'], 'type');
        $this->assertSame(['hours', 'access', 'social_links', 'faq', 'video', 'button_group'], $types);
        $this->assertSame('timeline', $stored['home'][0]['design']);
        $this->assertSame('map', $stored['home'][1]['design']);
        $this->assertSame('cards', $stored['home'][3]['design']);
        $this->assertSame('dQw4w9WgXcQ', $stored['home'][4]['youtube_id']);
        // 不正な SNS 種別は除外される
        $this->assertCount(1, $stored['home'][2]['links']);
        $this->assertSame('instagram', $stored['home'][2]['links'][0]['type']);

        // テーマ新キーが保存されている
        $storedSettings = ShopSettingStore::getLiffTabSettings($project->id);
        $this->assertSame('matcha', $storedSettings['_global']['theme_preset']);
        $this->assertSame('outline', $storedSettings['_global']['card_style']);
        $this->assertSame('square', $storedSettings['_global']['button_shape']);
        $this->assertSame('relaxed', $storedSettings['_global']['density']);
        $this->assertSame('image', $storedSettings['_global']['background_style']);
        $this->assertSame('narrow', $storedSettings['_global']['content_width']);
        $this->assertSame(55, $storedSettings['_global']['bg_overlay_opacity']);
        $this->assertSame('luxe', $storedSettings['_global']['system_ui_style']);
        $this->assertSame('elevated', $storedSettings['_global']['system_card_style']);
        $this->assertSame('arch', $storedSettings['_global']['system_media_shape']);
        $this->assertSame('hero', $storedSettings['_global']['system_header_style']);
        $this->assertSame('emblem', $storedSettings['_global']['member_card_design']);
        $this->assertSame('premium', $storedSettings['_global']['member_screen_design']);
        $this->assertSame('gallery', $storedSettings['_global']['coupon_screen_design']);
        $this->assertSame('timeline', $storedSettings['_global']['reservation_screen_design']);
        $this->assertSame('dashboard', $storedSettings['_global']['activity_screen_design']);

        // タブメニュー: 並び順・非表示・名称変更が保存され、不正タブは除外される
        $raw = ShopSettingStore::getValue('frontend_menu_items', null, $project->id);
        $this->assertIsArray($raw);
        $this->assertSame(['reservations', 'home', 'products'], array_column($raw, 'tab'));
        $this->assertSame('ご予約', $raw[0]['label']);
        $this->assertSame('ホーム', $raw[1]['label']); // 空labelはデフォルト補完
        $this->assertFalse($raw[2]['enabled']);

        // 表示メニュー（enabled のみ）には products が含まれない
        $visible = ShopSettingStore::getFrontendMenuItems($project->id);
        $this->assertNotContains('products', array_column($visible, 'tab'));
    }

    public function test_liff_designer_ブロックデザイン設定を保存できること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );

        $admin = AdminUser::create([
            'login_id' => 'admin4',
            'email' => 'admin4@example.com',
            'password' => 'password',
            'name' => 'Admin4',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $layouts = [
            'home' => [
                ['type' => 'member_summary', 'greeting' => 'ようこそ', 'design' => 'wallet'],
                ['type' => 'hero', 'title' => 'Hi', 'design' => 'minimal'],
                ['type' => 'data', 'source' => 'member_card', 'design' => 'premium', 'card_design' => 'emblem'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-star', 'label' => 'テスト', 'action' => '#products'],
                ]],
                ['type' => 'member_summary', 'design' => 'invalid_design'],
                ['type' => 'heading', 'text' => 'おすすめ', 'design' => 'underline'],
                ['type' => 'image', 'image_url' => 'https://example.com/a.jpg', 'design' => 'arch'],
                ['type' => 'collection', 'items' => [['title' => 'A']], 'design' => 'magazine'],
            ],
        ];

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.liff-designer.update', ['project_key' => $project->project_key]), [
                'layouts_json' => json_encode($layouts),
            ]);

        $response->assertRedirect(route('admin.settings.liff-designer', ['project_key' => $project->project_key]));

        $stored = ShopSettingStore::getLiffTabLayouts($project->id);
        $this->assertSame('wallet', $stored['home'][0]['design']);
        $this->assertSame('minimal', $stored['home'][1]['design']);
        $this->assertSame('premium', $stored['home'][2]['design']);
        $this->assertSame('emblem', $stored['home'][2]['card_design']);
        $this->assertSame('pill', $stored['home'][3]['design']);
        $this->assertSame('rewards', $stored['home'][4]['design']);
        $this->assertSame('underline', $stored['home'][5]['design']);
        $this->assertSame('arch', $stored['home'][6]['design']);
        $this->assertSame('magazine', $stored['home'][7]['design']);
    }

    public function test_liff_designer_不正なリンクは保存時に除去されること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );

        $admin = AdminUser::create([
            'login_id' => 'admin5',
            'email' => 'admin5@example.com',
            'password' => 'password',
            'name' => 'Admin5',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $layouts = [
            'home' => [
                [
                    'type' => 'banner',
                    'text' => 'テスト',
                    'action' => 'javascript:alert(1)',
                ],
                [
                    'type' => 'collection',
                    'items' => [
                        ['title' => 'A', 'action' => 'javascript:alert(1)'],
                        ['title' => 'B', 'action' => '#products'],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.liff-designer.update', ['project_key' => $project->project_key]), [
                'layouts_json' => json_encode($layouts),
            ]);

        $response->assertRedirect(route('admin.settings.liff-designer', ['project_key' => $project->project_key]));

        $stored = ShopSettingStore::getLiffTabLayouts($project->id);
        $this->assertArrayNotHasKey('action', $stored['home'][0]);
        $this->assertArrayNotHasKey('action', $stored['home'][1]['items'][0]);
        $this->assertSame('#products', $stored['home'][1]['items'][1]['action']);
    }

    public function test_liff_designer_friend_addとcardのアクションを保存できること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );

        $admin = AdminUser::create([
            'login_id' => 'admin6',
            'email' => 'admin6@example.com',
            'password' => 'password',
            'name' => 'Admin6',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $layouts = [
            'home' => [
                [
                    'type' => 'friend_add',
                    'text' => '友だちになる',
                    'color' => '#06C755',
                    'url' => 'https://line.me/R/ti/p/@example',
                    'design' => 'soft',
                ],
                [
                    'type' => 'card',
                    'title' => 'おすすめ',
                    'action' => '#products',
                    'design' => 'horizontal',
                ],
                [
                    'type' => 'banner',
                    'text' => 'お知らせ',
                    'action' => '#coupons',
                    'design' => 'promo',
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.liff-designer.update', ['project_key' => $project->project_key]), [
                'layouts_json' => json_encode($layouts),
            ]);

        $response->assertRedirect(route('admin.settings.liff-designer', ['project_key' => $project->project_key]));

        $stored = ShopSettingStore::getLiffTabLayouts($project->id);
        $this->assertSame('friend_add', $stored['home'][0]['type']);
        $this->assertSame('友だちになる', $stored['home'][0]['text']);
        $this->assertSame('https://line.me/R/ti/p/@example', $stored['home'][0]['url']);
        $this->assertSame('soft', $stored['home'][0]['design']);
        $this->assertSame('#products', $stored['home'][1]['action']);
        $this->assertSame('#coupons', $stored['home'][2]['action']);
    }

    public function test_liff_designer_保存時に接続設定を上書きしないこと(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );

        ShopSettingStore::setLineLiffId('existing-liff-id', $project->id);
        ShopSettingStore::setLineLiffUrl('https://example.com/liff', $project->id);

        $admin = AdminUser::create([
            'login_id' => 'admin7',
            'email' => 'admin7@example.com',
            'password' => 'password',
            'name' => 'Admin7',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->postJson(route('admin.settings.liff-designer.update', ['project_key' => $project->project_key]), [
                'layouts_json' => json_encode(['home' => [['type' => 'text', 'text' => 'ok']]]),
            ]);

        $response->assertOk();
        $response->assertJson(['message' => 'LIFFデザインを更新しました']);
        $this->assertSame('existing-liff-id', ShopSettingStore::getLineLiffId($project->id));
        $this->assertSame('https://example.com/liff', ShopSettingStore::getLineLiffUrl($project->id));
    }

    public function test_liff_designer_詳細レイアウトを保存できること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );

        $admin = AdminUser::create([
            'login_id' => 'admin_detail_layouts',
            'email' => 'admin_detail_layouts@example.com',
            'password' => 'password',
            'name' => 'Admin Detail',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $detailLayouts = [
            'product_detail' => [
                ['type' => 'heading', 'design' => 'eyebrow', 'text' => 'Menu'],
                ['type' => 'data', 'source' => 'product_detail'],
            ],
            'coupon_detail' => [
                ['type' => 'data', 'source' => 'coupon_detail'],
            ],
        ];

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.liff-designer.update', ['project_key' => $project->project_key]), [
                'layouts_json' => json_encode(['home' => [['type' => 'text', 'text' => 'ok']]]),
                'detail_layouts_json' => json_encode($detailLayouts),
            ]);

        $response->assertRedirect(route('admin.settings.liff-designer', ['project_key' => $project->project_key]));

        $stored = ShopSettingStore::getLiffDetailLayouts($project->id);
        $this->assertArrayHasKey('product_detail', $stored);
        $this->assertArrayHasKey('coupon_detail', $stored);
        $this->assertArrayHasKey('announcement_detail', $stored);
        $this->assertSame('product_detail', $stored['product_detail'][1]['source'] ?? null);
        $this->assertSame('coupon_detail', $stored['coupon_detail'][0]['source'] ?? null);
    }

    public function test_liff_designer_詳細レイアウト未送信時は既存を上書きしないこと(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );

        $admin = AdminUser::create([
            'login_id' => 'admin_detail_preserve',
            'email' => 'admin_detail_preserve@example.com',
            'password' => 'password',
            'name' => 'Admin Detail Preserve',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        ShopSettingStore::setLiffDetailLayouts([
            'product_detail' => [
                ['type' => 'heading', 'text' => 'Keep Me'],
                ['type' => 'data', 'source' => 'product_detail'],
            ],
        ], $project->id);

        $before = ShopSettingStore::getLiffDetailLayouts($project->id);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.liff-designer.update', ['project_key' => $project->project_key]), [
                'layouts_json' => json_encode(['home' => [['type' => 'text', 'text' => 'tabs only']]]),
            ]);

        $response->assertRedirect(route('admin.settings.liff-designer', ['project_key' => $project->project_key]));

        $after = ShopSettingStore::getLiffDetailLayouts($project->id);
        $this->assertSame('Keep Me', $after['product_detail'][0]['text'] ?? null);
        $this->assertSame($before['product_detail'][0]['text'] ?? null, $after['product_detail'][0]['text'] ?? null);
    }

    public function test_liff_designer_不正な詳細レイアウトは422になること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );

        $admin = AdminUser::create([
            'login_id' => 'admin_detail_invalid',
            'email' => 'admin_detail_invalid@example.com',
            'password' => 'password',
            'name' => 'Admin Detail Invalid',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.settings.liff-designer', ['project_key' => $project->project_key]))
            ->post(route('admin.settings.liff-designer.update', ['project_key' => $project->project_key]), [
                'layouts_json' => json_encode(['home' => [['type' => 'text', 'text' => 'ok']]]),
                'detail_layouts_json' => '{not-json',
            ]);

        $response->assertSessionHasErrors('detail_layouts');
    }

    public function test_liff_designer_appleブロックとテーマを保存できること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );

        $admin = AdminUser::create([
            'login_id' => 'admin_apple',
            'email' => 'admin_apple@example.com',
            'password' => 'password',
            'name' => 'Admin Apple',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $layouts = [
            'home' => [
                ['type' => 'stats_row', 'section_label' => 'Overview', 'items' => [
                    ['label' => 'ポイント', 'value' => '1,240', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => 'クーポン', 'value' => '3', 'hint' => '枚'],
                ]],
                ['type' => 'member_summary', 'design' => 'tier', 'greeting' => 'Club Member'],
                ['type' => 'banner', 'design' => 'countdown', 'text' => 'フラッシュセール', 'color' => '#007aff'],
                ['type' => 'collection', 'design' => 'chip_scroll', 'items' => [
                    ['title' => 'すべて', 'action' => '#products'],
                    ['title' => '人気', 'action' => '#products'],
                ]],
                ['type' => 'card', 'design' => 'featured', 'title' => 'Featured', 'description' => 'おすすめ'],
            ],
        ];

        $tabSettings = [
            '_global' => [
                'theme_preset' => 'apple_system',
                'tab_style' => 'floating',
                'bg_color' => '#f2f2f7',
                'member_screen_design' => 'apple',
                'coupon_screen_design' => 'glass',
                'reservation_screen_design' => 'apple',
            ],
        ];

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.liff-designer.update', ['project_key' => $project->project_key]), [
                'layouts_json' => json_encode($layouts),
                'tab_settings_json' => json_encode($tabSettings),
            ]);

        $response->assertRedirect(route('admin.settings.liff-designer', ['project_key' => $project->project_key]));

        $stored = ShopSettingStore::getLiffTabLayouts($project->id);
        $types = array_column($stored['home'], 'type');
        $this->assertSame(['stats_row', 'member_summary', 'banner', 'collection', 'card'], $types);
        $this->assertSame('tier', $stored['home'][1]['design']);
        $this->assertSame('countdown', $stored['home'][2]['design']);
        $this->assertSame('chip_scroll', $stored['home'][3]['design']);
        $this->assertSame('featured', $stored['home'][4]['design']);
        $this->assertCount(2, $stored['home'][0]['items']);
        $this->assertSame('points', $stored['home'][0]['items'][0]['field']);
        $this->assertArrayNotHasKey('field', $stored['home'][0]['items'][1]);

        $settings = ShopSettingStore::getLiffTabSettings($project->id);
        $this->assertSame('apple_system', $settings['_global']['theme_preset']);
        $this->assertSame('apple', $settings['_global']['member_screen_design']);
        $this->assertSame('glass', $settings['_global']['coupon_screen_design']);

        $templates = ShopSettingLiffDefaults::defaultLiffDesignerTemplates();
        $ids = array_column($templates, 'id');
        $this->assertContains('rg-suite-loyalty', $ids);
        $this->assertContains('rg-suite-sweets', $ids);
        $this->assertContains('rg-screen-rewards', $ids);
        $this->assertContains('rg-screen-barcode-dock', $ids);
        $this->assertSame('rg-suite-loyalty', $ids[0] ?? null);
    }

    public function test_liff_designer_会員証とスタンプのデザイン設定を保存できること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );

        $admin = AdminUser::create([
            'login_id' => 'admin_stamp_card_design',
            'email' => 'admin_stamp_card_design@example.com',
            'password' => 'password',
            'name' => 'Admin Stamp Card Design',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $layouts = [
            'stamps' => [
                [
                    'type' => 'data',
                    'source' => 'stamps',
                    'design' => 'premium',
                    'stamp_design' => 'wallet',
                    'stamp_slot_style' => 'rounded',
                    'stamp_filled_style' => 'solid',
                    'stamp_accent_color' => '#007aff',
                ],
            ],
        ];

        $tabSettings = [
            '_global' => [
                'member_card_design' => 'split_banner',
                'member_card_accent_color' => '#5856d6',
                'member_card_bg_style' => 'solid',
                'stamp_screen_design' => 'circle_board',
                'stamp_slot_style' => 'square',
                'stamp_filled_style' => 'icon',
                'stamp_accent_color' => '#ff9500',
            ],
        ];

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.liff-designer.update', ['project_key' => $project->project_key]), [
                'layouts_json' => json_encode($layouts),
                'tab_settings_json' => json_encode($tabSettings),
            ]);

        $response->assertRedirect(route('admin.settings.liff-designer', ['project_key' => $project->project_key]));

        $stored = ShopSettingStore::getLiffTabLayouts($project->id);
        $this->assertSame('wallet', $stored['stamps'][0]['stamp_design'] ?? null);
        $this->assertSame('rounded', $stored['stamps'][0]['stamp_slot_style'] ?? null);
        $this->assertSame('solid', $stored['stamps'][0]['stamp_filled_style'] ?? null);
        $this->assertSame('#007aff', $stored['stamps'][0]['stamp_accent_color'] ?? null);

        $settings = ShopSettingStore::getLiffTabSettings($project->id);
        $this->assertSame('split_banner', $settings['_global']['member_card_design']);
        $this->assertSame('#5856d6', $settings['_global']['member_card_accent_color']);
        $this->assertSame('solid', $settings['_global']['member_card_bg_style']);
        $this->assertSame('circle_board', $settings['_global']['stamp_screen_design']);
        $this->assertSame('square', $settings['_global']['stamp_slot_style']);
        $this->assertSame('icon', $settings['_global']['stamp_filled_style']);
        $this->assertSame('#ff9500', $settings['_global']['stamp_accent_color']);
    }

    public function test_liff_designer_外部ページタブを保存しフロントメニューへ反映できること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );

        $admin = AdminUser::create([
            'login_id' => 'admin_link_tab',
            'email' => 'admin_link_tab@example.com',
            'password' => 'password',
            'name' => 'Admin Link Tab',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $menuItems = [
            ['tab' => 'home', 'label' => 'ホーム', 'icon' => 'fa-house', 'enabled' => true],
            ['tab' => 'link_1', 'label' => '公式サイト', 'icon' => 'fa-globe', 'url' => 'https://example.com', 'open' => 'embed', 'enabled' => true],
            ['tab' => 'link_2', 'label' => 'NG', 'icon' => 'fa-globe', 'url' => 'http://insecure.example.com', 'open' => 'embed', 'enabled' => true],
        ];

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.liff-designer.update', ['project_key' => $project->project_key]), [
                'layouts_json' => json_encode(['home' => []]),
                'menu_items_json' => json_encode($menuItems),
            ]);

        $response->assertRedirect(route('admin.settings.liff-designer', ['project_key' => $project->project_key]));

        $raw = ShopSettingStore::getValue('frontend_menu_items', null, $project->id);
        $this->assertSame(['home', 'link_1'], array_column($raw, 'tab'));
        $this->assertSame('https://example.com', $raw[1]['url']);
        $this->assertSame('embed', $raw[1]['open']);

        $visible = ShopSettingStore::getFrontendMenuItems($project->id);
        $link = collect($visible)->firstWhere('tab', 'link_1');
        $this->assertNotNull($link);
        $this->assertSame('公式サイト', $link['label']);
        $this->assertSame('https://example.com', $link['url']);
    }
}
