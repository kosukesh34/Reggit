<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Project;
use App\Models\ShopSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiffDesignerTest extends TestCase
{
    use RefreshDatabase;

    public function test_liff_designer_管理者が_lif_fデザイナー画面にアクセスできること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
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
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
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

        $stored = ShopSetting::getLiffTabLayouts($project->id);
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
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
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
        $stored = ShopSetting::getLiffTabLayouts($project->id);
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
        $storedSettings = ShopSetting::getLiffTabSettings($project->id);
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
        $raw = ShopSetting::getValue('frontend_menu_items', null, $project->id);
        $this->assertIsArray($raw);
        $this->assertSame(['reservations', 'home', 'products'], array_column($raw, 'tab'));
        $this->assertSame('ご予約', $raw[0]['label']);
        $this->assertSame('ホーム', $raw[1]['label']); // 空labelはデフォルト補完
        $this->assertFalse($raw[2]['enabled']);

        // 表示メニュー（enabled のみ）には products が含まれない
        $visible = ShopSetting::getFrontendMenuItems($project->id);
        $this->assertNotContains('products', array_column($visible, 'tab'));
    }

    public function test_liff_designer_ブロックデザイン設定を保存できること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
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

        $stored = ShopSetting::getLiffTabLayouts($project->id);
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
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
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

        $stored = ShopSetting::getLiffTabLayouts($project->id);
        $this->assertArrayNotHasKey('action', $stored['home'][0]);
        $this->assertArrayNotHasKey('action', $stored['home'][1]['items'][0]);
        $this->assertSame('#products', $stored['home'][1]['items'][1]['action']);
    }

    public function test_liff_designer_friend_addとcardのアクションを保存できること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
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

        $stored = ShopSetting::getLiffTabLayouts($project->id);
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
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );

        ShopSetting::setLineLiffId('existing-liff-id', $project->id);
        ShopSetting::setLineLiffUrl('https://example.com/liff', $project->id);

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
        $this->assertSame('existing-liff-id', ShopSetting::getLineLiffId($project->id));
        $this->assertSame('https://example.com/liff', ShopSetting::getLineLiffUrl($project->id));
    }

    public function test_liff_designer_詳細レイアウトを保存できること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
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

        $stored = ShopSetting::getLiffDetailLayouts($project->id);
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
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );

        $admin = AdminUser::create([
            'login_id' => 'admin_detail_preserve',
            'email' => 'admin_detail_preserve@example.com',
            'password' => 'password',
            'name' => 'Admin Detail Preserve',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        ShopSetting::setLiffDetailLayouts([
            'product_detail' => [
                ['type' => 'heading', 'text' => 'Keep Me'],
                ['type' => 'data', 'source' => 'product_detail'],
            ],
        ], $project->id);

        $before = ShopSetting::getLiffDetailLayouts($project->id);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.liff-designer.update', ['project_key' => $project->project_key]), [
                'layouts_json' => json_encode(['home' => [['type' => 'text', 'text' => 'tabs only']]]),
            ]);

        $response->assertRedirect(route('admin.settings.liff-designer', ['project_key' => $project->project_key]));

        $after = ShopSetting::getLiffDetailLayouts($project->id);
        $this->assertSame('Keep Me', $after['product_detail'][0]['text'] ?? null);
        $this->assertSame($before['product_detail'][0]['text'] ?? null, $after['product_detail'][0]['text'] ?? null);
    }

    public function test_liff_designer_不正な詳細レイアウトは422になること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
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
}
