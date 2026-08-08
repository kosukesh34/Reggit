<?php

namespace App\Support;

/**
 * Reggit App Studio テンプレートカタログ。
 *
 * LINE ミニアプリ SaaS（会員・モバイルオーダー・予約・スタンプ/クーポン）の
 * ネイティブ CRM アプリ品質テンプレートを提供する。
 * 各業種が「別々の実アプリ」に感じられるよう、ホームの情報設計（IA）・
 * メニュー構成・主役ブロックを業種ごとに大きく変えている。
 * 既存レンダラー互換のブロック配列のみを返し、他クラスへの依存はゼロ。
 */
class LiffAppStudioTemplates
{
    private const MAIN_TABS = [
        'home', 'products', 'orders', 'stamps', 'coupons',
        'queue', 'reservations', 'member', 'gallery', 'announcements',
    ];

    /** タブの既定ラベル（menu 生成で使用） */
    private const TAB_LABELS = [
        'home' => 'ホーム',
        'products' => 'メニュー',
        'orders' => '注文',
        'stamps' => 'スタンプ',
        'coupons' => 'クーポン',
        'queue' => '順番待ち',
        'reservations' => '予約',
        'member' => '会員証',
        'gallery' => 'ギャラリー',
        'announcements' => 'お知らせ',
    ];

    /** タブの既定アイコン（menu 生成で使用） */
    private const TAB_ICONS = [
        'home' => 'fa-home',
        'products' => 'fa-utensils',
        'orders' => 'fa-receipt',
        'stamps' => 'fa-stamp',
        'coupons' => 'fa-ticket-alt',
        'queue' => 'fa-users',
        'reservations' => 'fa-calendar-check',
        'member' => 'fa-id-card',
        'gallery' => 'fa-images',
        'announcements' => 'fa-bullhorn',
    ];

    /**
     * @param  array<string, mixed>  $over
     * @return array<string, mixed>
     */
    public static function theme(array $over = []): array
    {
        $preset = (string) ($over['theme_preset'] ?? 'store');
        if ($preset === '') {
            $preset = 'store';
        }

        return array_merge(
            self::presetSeed($preset),
            [
                'theme_preset' => $preset,
                'app_display_name' => '〇〇店',
            ],
            $over
        );
    }

    /**
     * プリセットごとの見た目シード。業種テンプレが同じトーンにならないよう、ここで色・幅・密度を分ける。
     *
     * @return array<string, mixed>
     */
    private static function presetSeed(string $preset): array
    {
        $seeds = [
            'store' => [
                'accent_color' => '#0E7C66',
                'font_family' => 'sans',
                'surface_tone' => 'sage',
                'card_style' => 'flat',
                'button_shape' => 'pill',
                'tab_style' => 'floating',
                'density' => 'default',
                'background_style' => 'solid',
                'bg_color' => '#F1F5F4',
                'content_width' => 'comfy',
                'radius_scale' => 'soft',
                'system_ui_style' => 'refined',
                'system_header_style' => 'minimal',
                'member_screen_design' => 'apple',
                'stamp_screen_design' => 'grid_card',
                'stamp_slot_style' => 'circle',
                'stamp_filled_style' => 'image',
                'coupon_screen_design' => 'apple',
                'reservation_screen_design' => 'apple',
                'activity_screen_design' => 'cards',
            ],
            'apple_system' => [
                'accent_color' => '#007aff',
                'font_family' => 'sans',
                'surface_tone' => 'ios',
                'card_style' => 'elevated',
                'button_shape' => 'pill',
                'tab_style' => 'floating',
                'density' => 'default',
                'background_style' => 'solid',
                'bg_color' => '#f2f2f7',
                'content_width' => 'comfy',
                'radius_scale' => 'soft',
                'system_ui_style' => 'refined',
                'system_header_style' => 'minimal',
                'member_screen_design' => 'apple',
                'stamp_screen_design' => 'grid_card',
                'stamp_slot_style' => 'circle',
                'stamp_filled_style' => 'image',
                'coupon_screen_design' => 'apple',
                'reservation_screen_design' => 'apple',
                'activity_screen_design' => 'cards',
            ],
            'citrus' => [
                'accent_color' => '#ea580c',
                'font_family' => 'sans',
                'surface_tone' => 'warm',
                'card_style' => 'elevated',
                'button_shape' => 'pill',
                'tab_style' => 'floating',
                'density' => 'compact',
                'background_style' => 'solid',
                'bg_color' => '#fff7ed',
                'content_width' => 'edge',
                'radius_scale' => 'round',
                'system_ui_style' => 'compact',
                'system_header_style' => 'hero',
                'member_screen_design' => 'compact',
                'coupon_screen_design' => 'ticket',
                'reservation_screen_design' => 'cards',
                'activity_screen_design' => 'timeline',
            ],
            'lavender' => [
                'accent_color' => '#7c3aed',
                'font_family' => 'rounded',
                'surface_tone' => 'cool',
                'card_style' => 'elevated',
                'button_shape' => 'pill',
                'tab_style' => 'pill',
                'density' => 'relaxed',
                'background_style' => 'gradient',
                'bg_color' => '#faf5ff',
                'bg_gradient_start' => '#faf5ff',
                'bg_gradient_end' => '#f5f3ff',
                'content_width' => 'narrow',
                'radius_scale' => 'round',
                'system_ui_style' => 'luxe',
                'system_header_style' => 'minimal',
                'member_screen_design' => 'wallet',
                'coupon_screen_design' => 'gallery',
                'reservation_screen_design' => 'concierge',
                'activity_screen_design' => 'cards',
            ],
            'sand' => [
                'accent_color' => '#a8763e',
                'font_family' => 'rounded',
                'surface_tone' => 'warm',
                'card_style' => 'flat',
                'button_shape' => 'pill',
                'tab_style' => 'pill',
                'density' => 'default',
                'background_style' => 'solid',
                'bg_color' => '#faf5ee',
                'content_width' => 'comfy',
                'radius_scale' => 'round',
                'system_ui_style' => 'refined',
                'system_header_style' => 'minimal',
                'member_screen_design' => 'wallet',
                'coupon_screen_design' => 'ticket',
                'reservation_screen_design' => 'cards',
                'activity_screen_design' => 'cards',
            ],
            'sakura' => [
                'accent_color' => '#e11d48',
                'font_family' => 'serif-display',
                'surface_tone' => 'warm',
                'card_style' => 'elevated',
                'button_shape' => 'pill',
                'tab_style' => 'floating',
                'density' => 'relaxed',
                'background_style' => 'solid',
                'bg_color' => '#fff1f5',
                'content_width' => 'narrow',
                'radius_scale' => 'soft',
                'system_ui_style' => 'editorial',
                'system_header_style' => 'editorial',
                'member_screen_design' => 'wallet',
                'coupon_screen_design' => 'gallery',
                'reservation_screen_design' => 'concierge',
                'activity_screen_design' => 'cards',
            ],
            'noir' => [
                'accent_color' => '#1c1917',
                'font_family' => 'sans',
                'surface_tone' => 'paper',
                'card_style' => 'outline',
                'button_shape' => 'square',
                'tab_style' => 'default',
                'density' => 'default',
                'background_style' => 'solid',
                'bg_color' => '#f7f6f2',
                'content_width' => 'edge',
                'radius_scale' => 'compact',
                'system_ui_style' => 'editorial',
                'system_header_style' => 'hero',
                'member_screen_design' => 'premium',
                'coupon_screen_design' => 'luxury',
                'reservation_screen_design' => 'cards',
                'activity_screen_design' => 'dashboard',
            ],
            'slate' => [
                'accent_color' => '#0284c7',
                'font_family' => 'sans',
                'surface_tone' => 'cool',
                'card_style' => 'outline',
                'button_shape' => 'rounded',
                'tab_style' => 'default',
                'density' => 'default',
                'background_style' => 'solid',
                'bg_color' => '#f8fafc',
                'content_width' => 'comfy',
                'radius_scale' => 'default',
                'system_ui_style' => 'refined',
                'system_header_style' => 'minimal',
                'member_screen_design' => 'minimal',
                'coupon_screen_design' => 'compact',
                'reservation_screen_design' => 'timeline',
                'activity_screen_design' => 'timeline',
            ],
            // シャトレーゼ / コージーコーナー学習: 菓子・土産の温かいトーン
            'pastry' => [
                'accent_color' => '#5C3A21',
                'font_family' => 'sans',
                'surface_tone' => 'warm',
                'card_style' => 'elevated',
                'button_shape' => 'pill',
                'tab_style' => 'floating',
                'density' => 'default',
                'background_style' => 'solid',
                'bg_color' => '#F7F4F0',
                'content_width' => 'comfy',
                'radius_scale' => 'soft',
                'system_ui_style' => 'refined',
                'system_header_style' => 'minimal',
                'member_screen_design' => 'wallet',
                'stamp_screen_design' => 'grid_card',
                'coupon_screen_design' => 'ticket',
                'reservation_screen_design' => 'cards',
                'activity_screen_design' => 'cards',
            ],
            // GODIVA / Lindt 学習: ラグジュアリー菓子
            'chocolate' => [
                'accent_color' => '#C5A059',
                'font_family' => 'serif-display',
                'surface_tone' => 'paper',
                'card_style' => 'elevated',
                'button_shape' => 'pill',
                'tab_style' => 'pill',
                'density' => 'relaxed',
                'background_style' => 'solid',
                'bg_color' => '#FAF8F4',
                'content_width' => 'narrow',
                'radius_scale' => 'soft',
                'system_ui_style' => 'luxe',
                'system_header_style' => 'editorial',
                'member_screen_design' => 'wallet',
                'coupon_screen_design' => 'gallery',
                'reservation_screen_design' => 'concierge',
                'activity_screen_design' => 'cards',
            ],
        ];

        return $seeds[$preset] ?? $seeds['apple_system'];
    }

    /** @return array<string, array<string, string>> */
    public static function emptyTabs(): array
    {
        $settings = [];
        foreach (self::MAIN_TABS as $tab) {
            $settings[$tab] = ['background_color' => '', 'background_image' => ''];
        }

        return $settings;
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public static function defaultLayouts(): array
    {
        return self::loyaltyLayouts();
    }

    /** @return array<string, string> */
    public static function onboardingMap(): array
    {
        return [
            'restaurant' => 'rg-suite-loyalty',
            'cafe' => 'rg-suite-cafe',
            'fastfood' => 'rg-suite-order',
            'salon' => 'rg-suite-salon',
            'retail' => 'rg-suite-retail',
            'fitness' => 'rg-suite-loyalty',
            'clinic' => 'rg-suite-clinic',
            'sweets' => 'rg-suite-sweets',
            'bakery' => 'rg-suite-sweets',
            'souvenir' => 'rg-suite-sweets',
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function catalog(): array
    {
        return array_merge(self::suites(), self::screens());
    }

    /**
     * メニュー項目を構築する。$tabs は 'home' もしくは ['home', 'ラベル', 'fa-icon'] を要素に持つ配列。
     * 業種ごとに「表示するタブだけ」を並べることで、アプリ下部ナビが業種特化になる。
     *
     * @param  array<int, string|array{0: string, 1?: string, 2?: string}>  $tabs
     * @return array<int, array{tab: string, label: string, icon: string, enabled: bool}>
     */
    private static function menu(array $tabs): array
    {
        $items = [];
        foreach ($tabs as $entry) {
            $tab = is_array($entry) ? (string) $entry[0] : (string) $entry;
            $label = is_array($entry) && isset($entry[1]) ? (string) $entry[1] : (self::TAB_LABELS[$tab] ?? $tab);
            $icon = is_array($entry) && isset($entry[2]) ? (string) $entry[2] : (self::TAB_ICONS[$tab] ?? 'fa-circle');
            $items[] = ['tab' => $tab, 'label' => $label, 'icon' => $icon, 'enabled' => true];
        }

        return $items;
    }

    // ─── Suites ─────────────────────────────────────────────────────────

    /** @return array<int, array<string, mixed>> */
    private static function suites(): array
    {
        return [
            self::suiteEntry(
                'rg-suite-loyalty',
                '会員OS',
                'loyalty',
                'ポイント・ランク・特典を主役にしたロイヤリティアプリ。Starbucks Rewards のように会員証とリワードが中心で、ホームに商品棚は置かない。',
                [
                    'theme_preset' => 'store',
                    'accent_color' => '#0E7C66',
                    'member_card_design' => 'emblem',
                    'member_screen_design' => 'apple',
                    'density' => 'default',
                    'content_width' => 'comfy',
                    'card_style' => 'flat',
                    'button_shape' => 'pill',
                    'surface_tone' => 'sage',
                    'bg_color' => '#F1F5F4',
                    'app_display_name' => '〇〇店',
                ],
                self::menu(['home', 'stamps', 'coupons', 'member', 'announcements']),
                ''
            ),
            self::suiteEntry(
                'rg-suite-order',
                '注文OS',
                'order',
                'McDonald\'s / Uber Eats 風の最短注文アプリ。カウントダウン→カテゴリチップ→高密度な商品グリッドで、会員情報はホームに出さない。',
                [
                    'theme_preset' => 'citrus',
                    'accent_color' => '#ff9500',
                    'density' => 'compact',
                    'content_width' => 'edge',
                    'button_shape' => 'pill',
                    'tab_style' => 'floating',
                    'bg_color' => '#fffbf5',
                    'app_display_name' => '〇〇キッチン',
                ],
                self::menu(['home', 'products', 'orders', 'coupons', 'member']),
                '.builder-collection__grid{gap:8px;}'
            ),
            self::suiteEntry(
                'rg-suite-booking',
                '予約OS',
                'booking',
                'ホットペッパー風のサロン予約アプリ。次回予約カードを最上部に置き、空き枠→再予約までワンタップで完結する。',
                [
                    'theme_preset' => 'lavender',
                    'accent_color' => '#af52de',
                    'font_family' => 'rounded',
                    'density' => 'relaxed',
                    'content_width' => 'narrow',
                    'card_style' => 'elevated',
                    'app_display_name' => '〇〇サロン',
                ],
                self::menu(['home', ['reservations', '予約', 'fa-calendar-check'], 'products', 'gallery', 'member'])
            ),
            self::suiteEntry(
                'rg-suite-cafe',
                'カフェ専用',
                'cafe',
                'ブルーボトル風のカフェアプリ。朝の挨拶ヒーロー→本日のシグネチャー→スタンプ進捗の順で、ドリンク中心のやさしいトーン。',
                [
                    'theme_preset' => 'sand',
                    'accent_color' => '#8B5E3C',
                    'surface_tone' => 'warm',
                    'bg_color' => '#faf5ee',
                    'app_display_name' => '〇〇カフェ',
                ],
                self::menu(['home', ['products', 'メニュー', 'fa-mug-hot'], 'stamps', 'orders', 'member'])
            ),
            self::suiteEntry(
                'rg-suite-salon',
                '美容サロン専用',
                'salon',
                'Vogue のようなルックブック主導の美容室アプリ。全画面スライダー→マガジン風スタイル一覧で、チップUIは使わずセリフ体で上質に。',
                [
                    'theme_preset' => 'sakura',
                    'accent_color' => '#ff2d55',
                    'font_family' => 'serif-display',
                    'density' => 'relaxed',
                    'content_width' => 'narrow',
                    'app_display_name' => '〇〇ヘア',
                ],
                self::menu(['home', ['gallery', 'スタイル', 'fa-images'], ['reservations', '予約', 'fa-calendar-check'], 'products', 'member']),
                '.builder-heading__title,.builder-hero__title{font-family:"Playfair Display",Georgia,"Times New Roman",serif;letter-spacing:.01em;}'
            ),
            self::suiteEntry(
                'rg-suite-retail',
                '小売ショップ',
                'retail',
                'ZOZOTOWN 風のファッションECアプリ。セールカウントダウン→エディトリアルなルック→商品グリッドの編集的な構成で、角ばったボタン。',
                [
                    'theme_preset' => 'noir',
                    'accent_color' => '#111111',
                    'content_width' => 'edge',
                    'card_style' => 'outline',
                    'system_ui_style' => 'refined',
                    'app_display_name' => '〇〇ストア',
                ],
                self::menu(['home', ['products', '商品', 'fa-shopping-bag'], 'coupons', ['gallery', 'ルックブック', 'fa-images'], 'member', 'announcements']),
                '.builder-btn{border-radius:4px !important;}.builder-editorial-card{border-radius:2px;}'
            ),
            self::suiteEntry(
                'rg-suite-clinic',
                'クリニック',
                'clinic',
                'EPARK 風の医院アプリ。信頼感のあるリード文と待ち時間→診療時間→FAQ→アクセスの安心設計。商品棚やセールは一切出さない。',
                [
                    'theme_preset' => 'slate',
                    'accent_color' => '#0284c7',
                    'card_style' => 'outline',
                    'density' => 'default',
                    'app_display_name' => '〇〇クリニック',
                ],
                self::menu(['home', ['reservations', '予約', 'fa-calendar-check'], 'queue', ['member', '診察券', 'fa-id-card'], 'announcements']),
                '.builder-btn--cta{box-shadow:none !important;}'
            ),
            self::suiteEntry(
                'rg-suite-delivery',
                'デリバリー',
                'delivery',
                'Uber Eats / 出前館風のデリバリーアプリ。配達状況タイムラインを最上部に置き、配達促進→よく頼むメニューで再注文まで最短化。',
                [
                    'theme_preset' => 'citrus',
                    'accent_color' => '#ff3b30',
                    'density' => 'compact',
                    'content_width' => 'edge',
                    'bg_color' => '#fff7ed',
                    'app_display_name' => '〇〇デリバリー',
                ],
                self::menu(['home', 'products', ['orders', '注文状況', 'fa-truck'], 'coupons', 'member'])
            ),
            // シャトレーゼ / コージーコーナー / GODIVA スクショ学習
            self::suiteEntry(
                'rg-suite-sweets',
                '菓子・土産OS',
                'sweets',
                'シャトレーゼ／コージーコーナー風。会員証バーコードとポイントを最優先し、季節ヒーロー→カテゴリ丸写真→店頭受取の導線。商品棚より「会計で使えるカード」が主役。',
                [
                    'theme_preset' => 'pastry',
                    'accent_color' => '#5C3A21',
                    'surface_tone' => 'warm',
                    'bg_color' => '#F7F4F0',
                    'member_card_design' => 'emblem',
                    'member_screen_design' => 'wallet',
                    'coupon_screen_design' => 'ticket',
                    'density' => 'default',
                    'content_width' => 'comfy',
                    'button_shape' => 'pill',
                    'app_display_name' => '〇〇スイーツ',
                ],
                self::menu([
                    'home',
                    ['member', '会員証', 'fa-barcode'],
                    ['coupons', 'クーポン', 'fa-ticket-alt'],
                    ['products', 'スイーツ', 'fa-birthday-cake'],
                    'announcements',
                ]),
                '.theme-pastry .builder-btn--sticky{letter-spacing:.02em;}'
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $themeOver
     * @param  array<int, array{tab: string, label: string, icon: string, enabled: bool}>  $menu
     * @return array<string, mixed>
     */
    private static function suiteEntry(string $id, string $name, string $industry, string $description, array $themeOver, array $menu, string $customCss = ''): array
    {
        $layoutMethod = $industry.'Layouts';
        $categoryMap = [
            'loyalty' => 'loyalty',
            'order' => 'order',
            'booking' => 'booking',
            'cafe' => 'cafe',
            'salon' => 'salon',
            'retail' => 'retail',
            'clinic' => 'clinic',
            'delivery' => 'delivery',
            'sweets' => 'sweets',
        ];

        return [
            'id' => $id,
            'name' => $name,
            'scope' => 'all_tabs',
            'category' => $categoryMap[$industry] ?? 'suite',
            'description' => $description,
            'target_tabs' => self::MAIN_TABS,
            'menu' => $menu,
            'device_support' => ['line', 'ios', 'android'],
            'layouts' => self::{$layoutMethod}(),
            'tab_settings' => self::emptyTabs(),
            'custom_css' => $customCss,
            'theme' => self::theme($themeOver),
        ];
    }

    // ─── Screens ────────────────────────────────────────────────────────

    /** @return array<int, array<string, mixed>> */
    private static function screens(): array
    {
        return [
            self::screenEntry('rg-screen-rewards', 'リワードホーム', 'loyalty', 'Starbucks Rewards 風。ポイント・特典・ランクを一望するリワードダッシュボード。', ['home'], [], self::screenRewards()),
            self::screenEntry('rg-screen-flash', 'フラッシュセール', 'retail', 'SHEIN 風のタイムセール画面。カウントダウンと限定商品グリッドで即購買を促す。', ['home', 'products'], ['accent_color' => '#dc2626'], self::screenFlash()),
            self::screenEntry('rg-screen-pass', '会員証フォーカス', 'loyalty', 'Apple Wallet 風の会員証提示画面。QRとステータスだけに集中したミニマル構成。', ['member'], [], self::screenPass()),
            self::screenEntry('rg-screen-stamp', 'スタンプクエスト', 'loyalty', 'Pokémon GO 風のゲーミフィケーション。クエスト形式でスタンプ収集を促す。', ['stamps'], ['accent_color' => '#f59e0b'], self::screenStamp()),
            self::screenEntry('rg-screen-menu', 'メニュー棚', 'order', 'マクドナルド公式アプリ風。カテゴリ横スクロールと商品グリッドの注文特化画面。', ['products'], ['theme_preset' => 'citrus', 'accent_color' => '#ff9500'], self::screenMenu()),
            self::screenEntry('rg-screen-live-order', '注文ライブ状況', 'order', 'Uber Eats 風のトラッキング。注文のリアルタイム進捗を大きく表示する。', ['orders'], ['accent_color' => '#16a34a'], self::screenLiveOrder()),
            self::screenEntry('rg-screen-next-visit', '次回来店予約', 'booking', 'ホットペッパー風の再来店訴求。空き枠表示とワンタップ再予約に特化。', ['reservations'], ['theme_preset' => 'lavender', 'accent_color' => '#af52de'], self::screenNextVisit()),
            self::screenEntry('rg-screen-queue', '今すぐ順番待ち', 'booking', 'EPARK 順番待ち風。受付とLINE通知、待ち状況をシンプルに表示。', ['queue'], ['accent_color' => '#0284c7'], self::screenQueue()),
            self::screenEntry('rg-screen-lookbook', 'ルックブック', 'salon', 'Instagram 風の大判ギャラリー。スタイル写真を主役に予約へ誘導。', ['gallery'], ['theme_preset' => 'sakura', 'accent_color' => '#ff2d55'], self::screenLookbook()),
            self::screenEntry('rg-screen-digest', 'お知らせダイジェスト', 'screen', 'ニュースアプリ風。最新のお知らせをハイライト付きでまとめる。', ['announcements'], [], self::screenDigest()),
            self::screenEntry('rg-screen-store', '店舗情報', 'screen', 'Google ビジネスプロフィール風。営業時間・アクセス・SNS・FAQの案内。', ['home'], ['accent_color' => '#475569'], self::screenStore()),
            self::screenEntry('rg-screen-vip', 'VIPラウンジ', 'loyalty', 'American Express 風の上位会員ラウンジ。限定オファーと優待を編集的に見せる。', ['home', 'coupons'], ['theme_preset' => 'noir', 'accent_color' => '#92400e'], self::screenVip()),
            self::screenEntry('rg-screen-morning', 'モーニング特化カフェ', 'cafe', 'スターバックス朝アプリ風。時間帯限定のモーニングメニューを訴求。', ['home', 'products'], ['theme_preset' => 'sand', 'accent_color' => '#ca8a04'], self::screenMorning()),
            self::screenEntry('rg-screen-workout', 'ジムチェックイン', 'loyalty', 'Nike Training 風。来館チェックインと実績トラッキングを一画面に。', ['home', 'stamps', 'reservations'], ['accent_color' => '#16a34a'], self::screenWorkout()),
            self::screenEntry('rg-screen-sweets-home', '菓子店ホーム', 'sweets', 'コージーコーナー風。マイル/ポイント→季節ヒーロー→会員証・クーポン大ボタン。', ['home'], ['theme_preset' => 'pastry', 'accent_color' => '#5C3A21'], self::screenSweetsHome()),
            self::screenEntry('rg-screen-barcode-dock', 'バーコード提示', 'loyalty', 'シャトレーゼ風。ポイントカードとレジ提示バーコードだけに集中した会計画面。', ['member'], ['theme_preset' => 'pastry', 'accent_color' => '#5C3A21'], self::screenBarcodeDock()),
            self::screenEntry('rg-screen-chocolate-club', 'チョコクラブ', 'sweets', 'GODIVA / Lindt 風。ラグジュアリーな会員クラブとスタンプ特典。', ['home', 'member', 'coupons'], ['theme_preset' => 'chocolate', 'accent_color' => '#C5A059'], self::screenChocolateClub()),
            self::screenEntry('rg-screen-pickup-ready', '受取準備完了', 'order', 'Chipotle Pickup 風。時刻・場所・袋数・ヘルプで受取不安を消す。', ['orders'], ['theme_preset' => 'citrus', 'accent_color' => '#A81612'], self::screenPickupReady()),
            self::screenEntry('rg-screen-category-browse', 'カテゴリ閲覧', 'retail', 'UNIQLO 風。検索導線と写真カテゴリグリッドで商品発見。', ['home', 'products'], ['theme_preset' => 'noir', 'accent_color' => '#111111'], self::screenCategoryBrowse()),
        ];
    }

    /**
     * @param  array<int, string>  $targetTabs
     * @param  array<string, mixed>  $themeOver
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<string, mixed>
     */
    private static function screenEntry(string $id, string $name, string $category, string $description, array $targetTabs, array $themeOver, array $blocks): array
    {
        return [
            'id' => $id,
            'name' => $name,
            'category' => $category,
            'description' => $description,
            'target_tabs' => $targetTabs,
            'device_support' => ['line', 'ios', 'android'],
            'theme' => self::theme($themeOver),
            'blocks' => $blocks,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Suite Layouts — each returns array<string, array<int, array>>
    // 全 MAIN_TABS のキーを返す（デザイナー編集用）。アプリでの表示は menu が制御する。
    // ═══════════════════════════════════════════════════════════════════════

    /** Starbucks Rewards スクショ学習 — 挨拶→残高/進捗→特典交換CTA→会員証提示。商品棚なし */
    private static function loyaltyLayouts(): array
    {
        $a = '#00754A';

        return [
            'home' => [
                ['type' => 'member_summary', 'design' => 'rewards', 'greeting' => 'おはようございます'],
                ['type' => 'stats_row', 'section_label' => 'マイリワード', 'items' => [
                    ['label' => 'ポイント', 'value' => '70', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => 'ランク', 'value' => 'グリーン', 'hint' => '', 'field' => 'rank'],
                    ['label' => '次の特典まで', 'value' => '20', 'hint' => 'pt', 'field' => 'points_to_next'],
                ]],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-star', 'text' => 'スターリワードに交換できます', 'color' => $a, 'action' => '#coupons'],
                ['type' => 'card', 'design' => 'featured', 'title' => 'モバイルオーダー', 'description' => 'レジに並ばず、お店で受け取り', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=800', 'action' => '#products'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-barcode', 'label' => '会員証', 'action' => '#member'],
                    ['icon' => 'fa-gift', 'label' => '特典交換', 'action' => '#coupons'],
                    ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                    ['icon' => 'fa-envelope', 'label' => 'Inbox', 'action' => '#announcements'],
                ]],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'compact'],
                ['type' => 'button', 'design' => 'sticky', 'text' => '会員証を提示', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '交換メニュー', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => 'ポイントで交換できるアイテムはこちら。'],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '特典で交換', 'action' => '#coupons', 'color' => $a],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご利用履歴', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-coins', 'text' => 'ご利用ごとにポイントが貯まります', 'color' => $a],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '10個達成で特典クーポンをプレゼント', 'color' => $a],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-stamp', 'text' => 'ご来店ごとにスタンプ1個', 'color' => $a],
                    ['icon' => 'fa-crown', 'text' => 'ランクアップで特典が増えます', 'color' => $a],
                ]],
                ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン・特典', 'size' => 'large'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '順番待ちを開始', 'action' => '#queue', 'color' => $a, 'full_width' => true],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約', 'size' => 'large'],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約する', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'こんにちは'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'stats_row', 'items' => [
                    ['label' => 'ポイント', 'value' => '2,480', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => 'ランク', 'value' => 'ゴールド', 'hint' => '', 'field' => 'rank'],
                ]],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-qrcode', 'label' => '提示', 'action' => '#member'],
                    ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-wallet', 'label' => 'ウォレット', 'action' => '#member'],
                ]],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-wallet', 'text' => 'Apple / Google ウォレットに追加可能', 'color' => $a],
                ]],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ギャラリー', 'size' => 'large'],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }

    /** McDonald's / Chipotle スクショ学習 — 受取カード最上部、注文CTA明確、会員は裏方 */
    private static function orderLayouts(): array
    {
        $a = '#FFC72C';
        $ink = '#292929';

        return [
            'home' => [
                ['type' => 'card', 'design' => 'featured', 'title' => 'PICKUP #248', 'description' => '本日 12:00 受取 — 棚の2袋をご確認ください', 'image_url' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800', 'action' => '#orders'],
                ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-bag-shopping', 'text' => 'モバイルオーダーもデリバリーも — 公式アプリが便利', 'color' => $ink, 'text_color' => '#FFC72C', 'action' => '#products'],
                ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                    ['icon' => 'fa-utensils', 'label' => '注文する', 'action' => '#products'],
                    ['icon' => 'fa-receipt', 'label' => '受取状況', 'action' => '#orders'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-redo', 'label' => '再注文', 'action' => '#orders'],
                ]],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'メニューを開く', 'action' => '#products', 'color' => $ink, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'カートに進む', 'action' => '#order/cart', 'color' => $a, 'full_width' => true],
            ],
            'orders' => [
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-clock', 'text' => 'リアルタイムで更新', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-check-circle', 'text' => '受付 → 調理中 → 受取可能', 'color' => $a],
                ]],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'button', 'design' => 'cta', 'text' => '再注文する', 'action' => '#products', 'color' => $a],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '10個でお好きなセットメニュー無料', 'color' => $a],
                ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-ticket-alt', 'text' => 'アプリ限定クーポン', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'stats_row', 'items' => [
                    ['label' => '現在の待ち', 'value' => '3', 'hint' => '組'],
                    ['label' => '目安時間', 'value' => '8', 'hint' => '分'],
                ]],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '受付する', 'action' => '#queue', 'color' => $a, 'full_width' => true],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '来店予約で待ち時間なし。'],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約する', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'compact', 'greeting' => 'こんにちは'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-qrcode', 'label' => '提示', 'action' => '#member'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-history', 'label' => '履歴', 'action' => '#orders'],
                ]],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ギャラリー', 'size' => 'large'],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }

    /** サロン予約 風 — 次回予約カードを最上部に、空き枠と再予約が最優先 */
    private static function bookingLayouts(): array
    {
        $a = '#af52de';

        return [
            'home' => [
                ['type' => 'card', 'design' => 'featured', 'title' => '次回のご予約', 'description' => '7月25日（金）14:00〜 カット＋カラー / 担当 田中', 'image_url' => 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=800', 'action' => '#reservations'],
                ['type' => 'hero', 'design' => 'split', 'title' => '次はいつ来る？', 'subtitle' => '空き枠からワンタップで予約', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'stats_row', 'section_label' => 'マイサロン', 'items' => [
                    ['label' => '次回', 'value' => '7/25', 'hint' => ''],
                    ['label' => 'ポイント', 'value' => '1,120', 'hint' => 'pt', 'field' => 'points'],
                ]],
                ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                    ['icon' => 'fa-calendar-plus', 'label' => '予約する', 'action' => '#reservations'],
                    ['icon' => 'fa-exchange-alt', 'label' => '変更', 'action' => '#reservations'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                    ['icon' => 'fa-images', 'label' => 'スタイル', 'action' => '#gallery'],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => '予約する', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'メニュー', 'size' => 'large'],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'このメニューで予約', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご利用履歴', 'size' => 'large'],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'button', 'design' => 'cta', 'text' => '同じメニューで予約', 'action' => '#reservations', 'color' => $a],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '来店5回ごとにトリートメント無料', 'color' => $a],
                ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'stats_row', 'items' => [
                    ['label' => '現在の待ち', 'value' => '2', 'hint' => '組'],
                    ['label' => '目安', 'value' => '15', 'hint' => '分'],
                ]],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '受付する', 'action' => '#queue', 'color' => $a, 'full_width' => true],
            ],
            'reservations' => [
                ['type' => 'hero', 'design' => 'split', 'title' => 'ご予約', 'subtitle' => 'メニューと日時を選ぶだけ', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
                ['type' => 'list', 'style' => 'number', 'items' => ['メニューを選択', '日時を選ぶ', '予約を確定']],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => '新規予約', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'tier', 'greeting' => 'こんにちは'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-coins', 'text' => 'ご来店でポイントが貯まります', 'color' => $a],
                ]],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタイルギャラリー', 'size' => 'large'],
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'slide_height' => 220],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }

    /** Starbucks / ブルーボトル学習 — 挨拶→リワード進捗→オーダーCTA→Pay（会員証）常駐 */
    private static function cafeLayouts(): array
    {
        $a = '#8B5E3C';

        return [
            'home' => [
                ['type' => 'member_summary', 'design' => 'rewards', 'greeting' => 'おはようございます'],
                ['type' => 'stats_row', 'section_label' => '本日のリワード', 'items' => [
                    ['label' => 'スター', 'value' => '7', 'hint' => '/10', 'field' => 'points'],
                    ['label' => '次特典', 'value' => '3', 'hint' => '個', 'field' => 'points_to_next'],
                ]],
                ['type' => 'hero', 'design' => 'apple', 'eyebrow' => '今日の一杯', 'title' => 'シグネチャーをオーダー', 'subtitle' => 'レジに並ばず受け取り', 'image_url' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=1400&q=80'],
                ['type' => 'card', 'design' => 'featured', 'title' => '本日のシグネチャー', 'description' => '季節のラテ ¥620 — 焙煎したての一杯を', 'image_url' => 'https://images.unsplash.com/photo-1511920170033-f8396924c348?w=800', 'action' => '#products'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-mug-hot', 'text' => 'スタンプあと3個でドリンク1杯無料', 'color' => $a, 'action' => '#stamps'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-bag-shopping', 'label' => 'オーダー', 'action' => '#products'],
                    ['icon' => 'fa-barcode', 'label' => 'Pay', 'action' => '#member'],
                    ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'モバイルオーダーする', 'action' => '#products', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ドリンク & フード', 'size' => 'large'],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => 'カートを見る', 'action' => '#order/cart', 'color' => $a],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '注文状況', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-bell', 'text' => 'お呼び出しは LINE でお知らせ', 'color' => $a],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'button', 'design' => 'cta', 'text' => 'もう一杯注文', 'action' => '#products', 'color' => $a],
            ],
            'stamps' => [
                ['type' => 'member_summary', 'design' => 'compact'],
                ['type' => 'heading', 'design' => 'section', 'text' => 'コーヒースタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-mug-hot', 'text' => 'コーヒー10杯でお好きなドリンク1杯無料', 'color' => $a],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-mug-hot', 'text' => 'ドリンク注文ごとにスタンプ1個', 'color' => $a],
                    ['icon' => 'fa-seedling', 'text' => 'マイボトル持参でボーナス1個', 'color' => $a],
                    ['icon' => 'fa-gift', 'text' => '満了で無料ドリンクチケット', 'color' => $a],
                ]],
                ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-ticket-alt', 'text' => 'アプリ限定クーポン配信中', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '受付する', 'action' => '#queue', 'color' => $a, 'full_width' => true],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => 'お席のご予約はこちらから。'],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '席を予約', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'こんにちは'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-wallet', 'text' => 'Apple / Google ウォレットに追加可能', 'color' => $a],
                ]],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ギャラリー', 'size' => 'large'],
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'slide_height' => 200],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }

    /** ルックブック・ファースト美容室 — 全画面スライダー→マガジン、チップなし、セリフ体 */
    private static function salonLayouts(): array
    {
        $a = '#ff2d55';

        return [
            'home' => [
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'section_label' => 'スタイルギャラリー', 'slide_height' => 300],
                ['type' => 'member_summary', 'design' => 'wallet'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-calendar-plus', 'label' => '予約', 'action' => '#reservations'],
                    ['icon' => 'fa-images', 'label' => 'ギャラリー', 'action' => '#gallery'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'このスタイルで予約', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'メニュー', 'size' => 'large'],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'このメニューで予約', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '施術履歴', 'size' => 'large'],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'button', 'design' => 'cta', 'text' => '同じメニューで再予約', 'action' => '#reservations', 'color' => $a],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '来店5回でトリートメント無料', 'color' => $a],
                ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-ticket-alt', 'text' => '会員限定クーポン', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '受付する', 'action' => '#queue', 'color' => $a, 'full_width' => true],
            ],
            'reservations' => [
                ['type' => 'hero', 'design' => 'minimal', 'title' => '空き枠から予約', 'subtitle' => 'メニューと日時を選んで完了', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => '新規予約', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'こんにちは'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-qrcode', 'label' => '提示', 'action' => '#member'],
                    ['icon' => 'fa-calendar-plus', 'label' => '予約', 'action' => '#reservations'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ]],
            ],
            'gallery' => [
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'section_label' => 'スタイルギャラリー', 'slide_height' => 300],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'このスタイルで予約', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }

    /** UNIQLO スクショ学習 — 検索/スキャン導線・カテゴリ発見・下部に会員証 */
    private static function retailLayouts(): array
    {
        $a = '#111111';

        return [
            'home' => [
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-search', 'text' => '何をお探しですか？ — 商品検索・バーコードスキャン', 'color' => $a, 'text_color' => '#ffffff', 'action' => '#products'],
                ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-tag', 'text' => '会員登録で¥500クーポン — ¥5,000以上で利用可', 'color' => '#C2410C', 'text_color' => '#ffffff', 'action' => '#coupons'],
                ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                    ['icon' => 'fa-barcode', 'label' => '会員証', 'action' => '#member'],
                    ['icon' => 'fa-heart', 'label' => 'お気に入り', 'action' => '#gallery'],
                    ['icon' => 'fa-bell', 'label' => 'お知らせ', 'action' => '#announcements'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ]],
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'section_label' => 'カテゴリから探す', 'slide_height' => 180],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'card', 'design' => 'overlay', 'title' => 'New Arrivals', 'description' => '会員先行で公開中', 'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=800', 'action' => '#products'],
                ['type' => 'button', 'design' => 'sticky', 'text' => '会員証を提示', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '注文履歴', 'size' => 'large'],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'button', 'design' => 'cta', 'text' => '商品を見る', 'action' => '#products', 'color' => $a],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => 'お買い上げ5回で1,000ptプレゼント', 'color' => $a],
                ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
            ],
            'coupons' => [
                ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-ticket-alt', 'text' => '会員限定シークレットセール 残り12時間', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '受付する', 'action' => '#queue', 'color' => $a, 'full_width' => true],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご来店予約', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '店頭でのパーソナルスタイリングを予約。'],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約する', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => 'こんにちは'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-crown', 'text' => 'プラチナ会員は先行セール参加可能', 'color' => $a],
                ]],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ルックブック', 'size' => 'large'],
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'slide_height' => 240],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bullhorn', 'text' => 'セール・入荷情報をお届け', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }

    /** シャトレーゼ / コージーコーナー スクショ学習 — 会員証バーコード最優先・季節ヒーロー・店頭受取 */
    private static function sweetsLayouts(): array
    {
        $a = '#5C3A21';

        return [
            'home' => [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'おかえりなさい'],
                ['type' => 'stats_row', 'section_label' => 'マイポイント', 'items' => [
                    ['label' => 'ポイント', 'value' => '1,000', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => 'ランク', 'value' => 'レギュラー', 'hint' => '', 'field' => 'rank'],
                    ['label' => '次特典まで', 'value' => '200', 'hint' => 'pt', 'field' => 'points_to_next'],
                ]],
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'section_label' => '季節のおすすめ', 'slide_height' => 200],
                ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                    ['icon' => 'fa-barcode', 'label' => '会員証', 'action' => '#member'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-store', 'label' => '店頭受取', 'action' => '#products'],
                    ['icon' => 'fa-bullhorn', 'label' => '新作情報', 'action' => '#announcements'],
                ]],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-birthday-cake', 'text' => 'アプリが会員証に — レジでバーコードをご提示ください', 'color' => $a, 'action' => '#member'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'compact'],
                ['type' => 'card', 'design' => 'featured', 'title' => 'カテゴリから探す', 'description' => 'ケーキ・焼菓子・アイス・ギフト', 'image_url' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=800', 'action' => '#products'],
                ['type' => 'button', 'design' => 'sticky', 'text' => '会員証を提示', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スイーツを探す', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-shopping-bag', 'text' => 'ネット予約・店頭受け取りがスムーズ', 'color' => $a],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => '予約・購入へ', 'action' => '#order/cart', 'color' => $a, 'full_width' => true],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご注文・受取', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-store', 'text' => '店頭受取の準備ができたらお知らせします', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '対象商品のご購入でスタンプが貯まります', 'color' => $a],
                ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '限定クーポン', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-ticket-alt', 'text' => 'アプリ限定のお得なクーポン', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ケーキ予約・店頭受取', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '人気ギフトやデコレーションケーキを予約して店頭で受け取れます。'],
                ['type' => 'list', 'style' => 'number', 'items' => ['商品を選ぶ', '受取日時を指定', '店舗で受け取る']],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約する', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => '会員証'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'stats_row', 'items' => [
                    ['label' => 'ポイント', 'value' => '1,000', 'hint' => 'pt', 'field' => 'points'],
                    ['label' => '有効期限', 'value' => '2030.01', 'hint' => ''],
                ]],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-barcode', 'text' => 'レジでバーコードをご提示ください', 'color' => $a],
                    ['icon' => 'fa-store', 'text' => 'マイショップ店舗を設定すると便利です', 'color' => $a],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'バーコードを拡大', 'action' => '#member', 'color' => $a, 'full_width' => true],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '季節のスイーツ', 'size' => 'large'],
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'slide_height' => 220],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '最新スイーツ情報', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bullhorn', 'text' => '新作・限定情報をお届け', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }

    /** クリニック — 信頼感重視、待ち時間→診療時間→FAQ→アクセス、商品棚・セール一切なし */
    private static function clinicLayouts(): array
    {
        $a = '#0284c7';

        return [
            'home' => [
                ['type' => 'text', 'design' => 'lead', 'text' => '地域のかかりつけ医として、待ち時間やご予約をアプリから確認いただけます。'],
                ['type' => 'stats_row', 'section_label' => '本日の状況', 'items' => [
                    ['label' => '待ち時間', 'value' => '12', 'hint' => '分'],
                    ['label' => '待ち人数', 'value' => '5', 'hint' => '人'],
                    ['label' => '次回予約', 'value' => '8/3', 'hint' => ''],
                ]],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-info-circle', 'text' => '本日の診察は通常通り受付中です', 'color' => $a],
                ['type' => 'hours', 'design' => 'card', 'title' => '診療時間', 'rows' => [
                    ['day' => '月〜金', 'time' => '9:00 - 18:00'],
                    ['day' => '土', 'time' => '9:00 - 13:00'],
                    ['day' => '日・祝', 'time' => '休診'],
                ]],
                ['type' => 'faq', 'design' => 'accordion', 'title' => 'よくある質問', 'items' => [
                    ['q' => '初診の持ち物は？', 'a' => '保険証・お薬手帳をお持ちください。'],
                    ['q' => 'キャンセルできますか？', 'a' => '予約詳細から変更・キャンセルが可能です。'],
                    ['q' => '駐車場はありますか？', 'a' => '専用駐車場を10台分ご用意しています。'],
                ]],
                ['type' => 'access', 'design' => 'card', 'title' => 'アクセス', 'address' => '東京都〇〇区〇〇 1-2-3', 'note' => '〇〇駅 徒歩5分'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約する', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '診療メニュー', 'size' => 'large'],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ],
            'orders' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '受診履歴', 'size' => 'large'],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '定期受診でポイントが貯まります', 'color' => $a],
                ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち受付', 'size' => 'large'],
                ['type' => 'stats_row', 'items' => [
                    ['label' => '待ち人数', 'value' => '5', 'hint' => '人'],
                    ['label' => '目安', 'value' => '20', 'hint' => '分'],
                ]],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-mobile-alt', 'text' => '順番が近づいたら LINE で通知', 'color' => $a],
                    ['icon' => 'fa-walking', 'text' => '呼び出しまで外出いただけます', 'color' => $a],
                ]],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '順番待ちを開始', 'action' => '#queue', 'color' => $a, 'full_width' => true],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => 'ご希望の診療科と日時を選択してください。'],
                ['type' => 'list', 'style' => 'number', 'items' => ['診療科を選択', '希望日時を選ぶ', '予約を確定']],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約する', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'こんにちは'],
                ['type' => 'heading', 'design' => 'section', 'text' => '診察券', 'size' => 'large'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-qrcode', 'text' => '受付時にこの画面をご提示ください', 'color' => $a],
                ]],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '院内のご案内', 'size' => 'large'],
                ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'slide_height' => 200],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bullhorn', 'text' => '休診日・予防接種のご案内', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }

    /** デリバリー — 配達状況タイムラインを最上部に、再注文まで最短化 */
    private static function deliveryLayouts(): array
    {
        $a = '#ff3b30';

        return [
            'home' => [
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-motorcycle', 'text' => '配達中 — あと約12分でお届け', 'color' => $a, 'text_color' => '#ffffff', 'action' => '#orders'],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-tag', 'text' => '配達料無料キャンペーン中 残り3日', 'color' => $a, 'text_color' => '#ffffff', 'action' => '#products'],
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                    ['icon' => 'fa-utensils', 'label' => '注文する', 'action' => '#products'],
                    ['icon' => 'fa-truck', 'label' => '配達状況', 'action' => '#orders'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-history', 'label' => '再注文', 'action' => '#orders'],
                ]],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'もう一度注文', 'action' => '#products', 'color' => $a, 'full_width' => true],
            ],
            'products' => [
                ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'sticky', 'text' => 'カートに進む', 'action' => '#order/cart', 'color' => $a, 'full_width' => true],
            ],
            'orders' => [
                ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-truck', 'text' => 'リアルタイムで配達状況を表示', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'list', 'style' => 'number', 'items' => ['注文受付', 'レストランで調理中', '配達員が受け取り', '配達中', 'お届け完了']],
                ['type' => 'icon_text', 'rows' => [
                    ['icon' => 'fa-bell', 'text' => '配達完了で LINE 通知', 'color' => $a],
                ]],
                ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
                ['type' => 'button', 'design' => 'cta', 'text' => '再注文する', 'action' => '#products', 'color' => $a],
            ],
            'stamps' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプ', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => '注文5回で500円OFFクーポン', 'color' => $a],
                ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
            ],
            'coupons' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'クーポン', 'size' => 'large'],
                ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-ticket-alt', 'text' => '期間限定クーポン配信中', 'color' => $a, 'text_color' => '#ffffff'],
                ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ],
            'queue' => [
                ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち', 'size' => 'large'],
                ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
            ],
            'reservations' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ご予約', 'size' => 'large'],
                ['type' => 'text', 'design' => 'lead', 'text' => '事前予約で受け取り時間を指定できます。'],
                ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
                ['type' => 'button', 'design' => 'cta', 'text' => '予約注文', 'action' => '#reservations', 'color' => $a, 'full_width' => true],
            ],
            'member' => [
                ['type' => 'member_summary', 'design' => 'compact', 'greeting' => 'こんにちは'],
                ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
                ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                    ['icon' => 'fa-qrcode', 'label' => '提示', 'action' => '#member'],
                    ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                    ['icon' => 'fa-history', 'label' => '注文履歴', 'action' => '#orders'],
                ]],
            ],
            'gallery' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'ギャラリー', 'size' => 'large'],
                ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ],
            'announcements' => [
                ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
                ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Screen blocks — 単一目的の完成された1画面。用途に応じて sticky CTA を使う。
    // ═══════════════════════════════════════════════════════════════════════

    /** @return array<int, array<string, mixed>> */
    private static function screenRewards(): array
    {
        return [
            ['type' => 'member_summary', 'design' => 'rewards', 'greeting' => 'おかえりなさい'],
            ['type' => 'stats_row', 'section_label' => 'マイリワード', 'items' => [
                ['label' => 'ポイント', 'value' => '3,280', 'hint' => 'pt', 'field' => 'points'],
                ['label' => 'ランク', 'value' => 'ゴールド', 'hint' => '', 'field' => 'rank'],
                ['label' => '次回特典', 'value' => '720', 'hint' => 'ptで達成', 'field' => 'points_to_next'],
            ]],
            ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-crown', 'text' => 'ゴールド会員限定 — 今週末ポイント3倍', 'color' => '#5856d6'],
            ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                ['icon' => 'fa-qrcode', 'label' => '会員証', 'action' => '#member'],
                ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
            ]],
            ['type' => 'button', 'design' => 'sticky', 'text' => '会員証を提示', 'action' => '#member', 'color' => '#5856d6', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenFlash(): array
    {
        return [
            ['type' => 'banner', 'design' => 'countdown', 'icon' => 'fa-fire', 'text' => 'フラッシュセール — 本日23:59まで最大60%OFF', 'color' => '#dc2626', 'text_color' => '#ffffff', 'action' => '#products'],
            ['type' => 'stats_row', 'items' => [
                ['label' => '残り時間', 'value' => '5:42:18', 'hint' => ''],
                ['label' => '対象商品', 'value' => '48', 'hint' => '点'],
            ]],
            ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'sticky', 'text' => 'セール商品をすべて見る', 'action' => '#products', 'color' => '#dc2626', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenPass(): array
    {
        return [
            ['type' => 'member_summary', 'design' => 'emblem', 'greeting' => '会員証'],
            ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
            ['type' => 'stats_row', 'items' => [
                ['label' => 'ポイント', 'value' => '2,480', 'hint' => 'pt', 'field' => 'points'],
                ['label' => 'ランク', 'value' => 'ゴールド', 'hint' => '', 'field' => 'rank'],
            ]],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-qrcode', 'text' => 'レジでこの画面をご提示ください', 'color' => '#5856d6'],
                ['icon' => 'fa-wallet', 'text' => 'Apple / Google ウォレットに追加可能', 'color' => '#5856d6'],
            ]],
            ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                ['icon' => 'fa-stamp', 'label' => 'スタンプ', 'action' => '#stamps'],
                ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
                ['icon' => 'fa-history', 'label' => '履歴', 'action' => '#orders'],
            ]],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenStamp(): array
    {
        return [
            ['type' => 'member_summary', 'design' => 'compact'],
            ['type' => 'heading', 'design' => 'section', 'text' => 'スタンプクエスト', 'size' => 'large'],
            ['type' => 'stats_row', 'section_label' => '進捗', 'items' => [
                ['label' => '今月', 'value' => '7', 'hint' => '/10'],
                ['label' => '累計', 'value' => '42', 'hint' => '個'],
                ['label' => '達成特典', 'value' => '4', 'hint' => '回'],
            ]],
            ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-trophy', 'text' => 'あと3個で「ドリンク無料」達成！', 'color' => '#f59e0b'],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-stamp', 'text' => 'ご来店・ご注文ごとにスタンプ1個', 'color' => '#f59e0b'],
                ['icon' => 'fa-gift', 'text' => '10個達成: ドリンク無料', 'color' => '#f59e0b'],
                ['icon' => 'fa-crown', 'text' => '30個達成: ランクアップ', 'color' => '#f59e0b'],
            ]],
            ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'sticky', 'text' => '特典を確認', 'action' => '#coupons', 'color' => '#f59e0b', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenMenu(): array
    {
        return [
            ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'sticky', 'text' => 'カートに進む', 'action' => '#order/cart', 'color' => '#ff9500', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenLiveOrder(): array
    {
        return [
            ['type' => 'heading', 'design' => 'section', 'text' => '注文ステータス', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-clock', 'text' => '調理中 — あと約8分', 'color' => '#16a34a', 'text_color' => '#ffffff'],
            ['type' => 'stats_row', 'items' => [
                ['label' => '注文番号', 'value' => '#248', 'hint' => ''],
                ['label' => '予定', 'value' => '12:35', 'hint' => ''],
                ['label' => 'ステータス', 'value' => '調理中', 'hint' => ''],
            ]],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-check-circle', 'text' => '注文受付 12:20', 'color' => '#16a34a'],
                ['icon' => 'fa-fire', 'text' => '調理中 12:22〜', 'color' => '#16a34a'],
                ['icon' => 'fa-bell', 'text' => '準備完了で LINE 通知', 'color' => '#6b7280'],
            ]],
            ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
            ['type' => 'button', 'design' => 'cta', 'text' => '追加注文する', 'action' => '#products', 'color' => '#16a34a'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenNextVisit(): array
    {
        return [
            ['type' => 'hero', 'design' => 'split', 'title' => '次回のご予約', 'subtitle' => '空いている日時から最短で予約', 'image_url' => 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=1400&q=80'],
            ['type' => 'stats_row', 'items' => [
                ['label' => '前回来店', 'value' => '6/15', 'hint' => ''],
                ['label' => '推奨', 'value' => '4週後', 'hint' => ''],
                ['label' => '空き', 'value' => '5', 'hint' => '枠'],
            ]],
            ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-calendar-check', 'text' => '前回と同じメニューで再予約できます', 'color' => '#af52de', 'action' => '#reservations'],
            ['type' => 'list', 'style' => 'number', 'items' => ['メニューを確認', '希望日時を選択', '予約を確定']],
            ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'sticky', 'text' => '空き枠を見る', 'action' => '#reservations', 'color' => '#af52de', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenQueue(): array
    {
        return [
            ['type' => 'heading', 'design' => 'section', 'text' => '順番待ち受付', 'size' => 'large'],
            ['type' => 'stats_row', 'section_label' => '現在の状況', 'items' => [
                ['label' => '待ち組数', 'value' => '4', 'hint' => '組'],
                ['label' => '目安時間', 'value' => '15', 'hint' => '分'],
            ]],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-user-friends', 'text' => '人数を入力して受付', 'color' => '#0284c7'],
                ['icon' => 'fa-mobile-alt', 'text' => '順番が近づいたら LINE で通知', 'color' => '#0284c7'],
                ['icon' => 'fa-map-marker-alt', 'text' => '呼び出しまで外出OK', 'color' => '#0284c7'],
            ]],
            ['type' => 'data', 'source' => 'queue', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'cta', 'text' => '受付を開始する', 'action' => '#queue', 'color' => '#0284c7', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenLookbook(): array
    {
        return [
            ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'section_label' => 'スタイルギャラリー', 'slide_height' => 300],
            ['type' => 'data', 'source' => 'gallery', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'sticky', 'text' => 'このスタイルで予約', 'action' => '#reservations', 'color' => '#ff2d55', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenDigest(): array
    {
        return [
            ['type' => 'heading', 'design' => 'section', 'text' => 'お知らせ', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bullhorn', 'text' => '最新のお知らせ', 'color' => '#007aff', 'text_color' => '#ffffff'],
            ['type' => 'data', 'source' => 'announcements', 'design' => 'cards'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenStore(): array
    {
        return [
            ['type' => 'heading', 'design' => 'section', 'text' => '店舗情報', 'size' => 'large'],
            ['type' => 'hours', 'design' => 'card', 'title' => '営業時間', 'rows' => [
                ['day' => '月〜金', 'time' => '10:00 - 20:00'],
                ['day' => '土日祝', 'time' => '10:00 - 18:00'],
                ['day' => '定休日', 'time' => '水曜'],
            ]],
            ['type' => 'access', 'design' => 'card', 'title' => 'アクセス', 'address' => '東京都〇〇区〇〇 1-2-3 〇〇ビル 1F', 'note' => '〇〇駅 徒歩3分'],
            ['type' => 'social_links', 'design' => 'icon_row', 'links' => [
                ['type' => 'instagram', 'url' => 'https://instagram.com/'],
                ['type' => 'x', 'url' => 'https://x.com/'],
                ['type' => 'line', 'url' => 'https://line.me/'],
            ]],
            ['type' => 'faq', 'design' => 'accordion', 'title' => 'よくある質問', 'items' => [
                ['q' => '駐車場はありますか？', 'a' => '専用駐車場を5台分ご用意しています。'],
                ['q' => '予約なしでも利用できますか？', 'a' => 'はい、当日のご来店も歓迎です。'],
                ['q' => '支払い方法は？', 'a' => '現金・クレジットカード・電子マネーに対応しています。'],
            ]],
            ['type' => 'friend_add', 'design' => 'solid', 'text' => 'LINE公式アカウントを友だち追加'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenVip(): array
    {
        return [
            ['type' => 'hero', 'design' => 'editorial', 'eyebrow' => 'VIPラウンジ', 'title' => 'プラチナ会員限定', 'subtitle' => '特別なオファーと優待をお楽しみください', 'image_url' => 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=1400&q=80'],
            ['type' => 'member_summary', 'design' => 'wallet'],
            ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gem', 'text' => '年間お買い上げ ¥100,000 でプラチナ維持', 'color' => '#92400e'],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-crown', 'text' => 'ポイント2倍還元', 'color' => '#92400e'],
                ['icon' => 'fa-gift', 'text' => '毎月の限定特典', 'color' => '#92400e'],
                ['icon' => 'fa-concierge-bell', 'text' => '優先予約', 'color' => '#92400e'],
            ]],
            ['type' => 'button', 'design' => 'cta', 'text' => '限定特典を見る', 'action' => '#coupons', 'color' => '#92400e', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenMorning(): array
    {
        return [
            ['type' => 'hero', 'design' => 'apple', 'eyebrow' => 'おはようございます', 'title' => 'モーニングメニュー', 'subtitle' => '7:00〜10:30限定のお得なセット', 'image_url' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1400&q=80'],
            ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-clock', 'text' => 'モーニングタイム 7:00〜10:30', 'color' => '#ca8a04'],
            ['type' => 'stats_row', 'items' => [
                ['label' => 'スタンプ', 'value' => '7', 'hint' => '/10'],
                ['label' => 'ポイント', 'value' => '340', 'hint' => 'pt', 'field' => 'points'],
            ]],
            ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'cta', 'text' => 'モーニングを注文', 'action' => '#products', 'color' => '#ca8a04', 'full_width' => true],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function screenWorkout(): array
    {
        return [
            ['type' => 'member_summary', 'design' => 'tier', 'greeting' => '今日もお疲れさまです'],
            ['type' => 'stats_row', 'section_label' => '今月の記録', 'items' => [
                ['label' => '来館', 'value' => '12', 'hint' => '回'],
                ['label' => '連続', 'value' => '5', 'hint' => '日'],
                ['label' => 'ポイント', 'value' => '1,200', 'hint' => 'pt', 'field' => 'points'],
            ]],
            ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-medal', 'text' => '今月の来館15回でボーナスポイント', 'color' => '#16a34a', 'action' => '#stamps'],
            ['type' => 'quick_actions', 'design' => 'pill', 'actions' => [
                ['icon' => 'fa-dumbbell', 'label' => 'チェックイン', 'action' => '#stamps'],
                ['icon' => 'fa-calendar-check', 'label' => 'レッスン予約', 'action' => '#reservations'],
                ['icon' => 'fa-id-card', 'label' => '会員証', 'action' => '#member'],
            ]],
            ['type' => 'data', 'source' => 'reservations', 'design' => 'cards'],
            ['type' => 'data', 'source' => 'stamps', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'sticky', 'text' => 'チェックイン', 'action' => '#stamps', 'color' => '#16a34a', 'full_width' => true],
        ];
    }

    /** コージーコーナー風ホーム */
    /** @return array<int, array<string, mixed>> */
    private static function screenSweetsHome(): array
    {
        $a = '#5C3A21';

        return [
            ['type' => 'member_summary', 'design' => 'wallet', 'greeting' => 'こんにちは'],
            ['type' => 'stats_row', 'items' => [
                ['label' => 'マイル', 'value' => '1,000', 'hint' => 'pt', 'field' => 'points'],
                ['label' => '使えるクーポン', 'value' => '3', 'hint' => '枚'],
            ]],
            ['type' => 'slider', 'source' => 'gallery', 'design' => 'hero', 'section_label' => 'おすすめスイーツ', 'slide_height' => 220],
            ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                ['icon' => 'fa-barcode', 'label' => '会員証', 'action' => '#member'],
                ['icon' => 'fa-ticket-alt', 'label' => 'クーポン', 'action' => '#coupons'],
            ]],
            ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-percent', 'text' => 'お得情報満載 — マイルプログラム対象店舗', 'color' => $a],
            ['type' => 'button', 'design' => 'sticky', 'text' => '会員証を提示', 'action' => '#member', 'color' => $a, 'full_width' => true],
        ];
    }

    /** シャトレーゼ風バーコード提示 */
    /** @return array<int, array<string, mixed>> */
    private static function screenBarcodeDock(): array
    {
        $a = '#5C3A21';

        return [
            ['type' => 'heading', 'design' => 'section', 'text' => 'ポイントが貯まる・使える', 'size' => 'large'],
            ['type' => 'member_summary', 'design' => 'wallet'],
            ['type' => 'data', 'source' => 'member_card', 'card_design' => 'emblem'],
            ['type' => 'stats_row', 'items' => [
                ['label' => 'ポイント', 'value' => '10,000', 'hint' => 'pt', 'field' => 'points'],
                ['label' => '有効期限', 'value' => '2030.01.01', 'hint' => ''],
            ]],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-barcode', 'text' => 'レジでバーコードをご提示ください', 'color' => $a],
            ]],
            ['type' => 'button', 'design' => 'sticky', 'text' => 'バーコードを拡大表示', 'action' => '#member', 'color' => $a, 'full_width' => true],
        ];
    }

    /** GODIVA / Lindt 風クラブ */
    /** @return array<int, array<string, mixed>> */
    private static function screenChocolateClub(): array
    {
        $a = '#C5A059';

        return [
            ['type' => 'hero', 'design' => 'editorial', 'eyebrow' => 'Member Club', 'title' => 'あなたにぴったりな Happiness を', 'subtitle' => 'いつでもどこでも、もっと楽しく', 'image_url' => 'https://images.unsplash.com/photo-1549007994-cb72beaad946?w=1400&q=80'],
            ['type' => 'member_summary', 'design' => 'wallet'],
            ['type' => 'stats_row', 'section_label' => 'クラブステータス', 'items' => [
                ['label' => 'ランクP', 'value' => '2,400', 'hint' => '', 'field' => 'points'],
                ['label' => '交換P', 'value' => '180', 'hint' => ''],
                ['label' => 'スタンプ', 'value' => '4', 'hint' => '/8'],
            ]],
            ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-gift', 'text' => 'スタンプ達成で限定クーポン', 'color' => $a, 'action' => '#stamps'],
            ['type' => 'data', 'source' => 'coupons', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'cta', 'text' => '会員証を提示', 'action' => '#member', 'color' => '#1c1917', 'full_width' => true],
        ];
    }

    /** Chipotle Pickup 風 */
    /** @return array<int, array<string, mixed>> */
    private static function screenPickupReady(): array
    {
        $a = '#A81612';

        return [
            ['type' => 'heading', 'design' => 'section', 'text' => 'PICKUP', 'size' => 'large'],
            ['type' => 'banner', 'design' => 'pill', 'icon' => 'fa-bag-shopping', 'text' => '受取準備完了 — 12:00 / 棚の2袋', 'color' => $a, 'text_color' => '#ffffff'],
            ['type' => 'stats_row', 'items' => [
                ['label' => '受取時刻', 'value' => '12:00', 'hint' => ''],
                ['label' => '袋数', 'value' => '2', 'hint' => '袋'],
                ['label' => '店舗', 'value' => '本店', 'hint' => ''],
            ]],
            ['type' => 'access', 'design' => 'card', 'title' => '受取店舗', 'address' => '東京都〇〇区〇〇 1-2-3', 'note' => 'ピックアップ棚でお受け取りください'],
            ['type' => 'icon_text', 'rows' => [
                ['icon' => 'fa-map-marker-alt', 'text' => '店舗マップで場所を確認', 'color' => $a],
                ['icon' => 'fa-comment', 'text' => 'わからないときはスタッフへ', 'color' => $a],
            ]],
            ['type' => 'data', 'source' => 'orders', 'design' => 'timeline'],
            ['type' => 'button', 'design' => 'sticky', 'text' => '注文詳細を見る', 'action' => '#orders', 'color' => $a, 'full_width' => true],
        ];
    }

    /** UNIQLO 風カテゴリ */
    /** @return array<int, array<string, mixed>> */
    private static function screenCategoryBrowse(): array
    {
        $a = '#111111';

        return [
            ['type' => 'banner', 'design' => 'promo', 'icon' => 'fa-search', 'text' => '何をお探しですか？', 'color' => $a, 'text_color' => '#ffffff', 'action' => '#products'],
            ['type' => 'heading', 'design' => 'section', 'text' => 'カテゴリから探す', 'size' => 'large'],
            ['type' => 'quick_actions', 'design' => 'grid', 'actions' => [
                ['icon' => 'fa-female', 'label' => 'WOMEN', 'action' => '#products'],
                ['icon' => 'fa-male', 'label' => 'MEN', 'action' => '#products'],
                ['icon' => 'fa-child', 'label' => 'KIDS', 'action' => '#products'],
                ['icon' => 'fa-barcode', 'label' => '会員証', 'action' => '#member'],
            ]],
            ['type' => 'data', 'source' => 'products', 'design' => 'cards'],
            ['type' => 'button', 'design' => 'sticky', 'text' => '商品一覧へ', 'action' => '#products', 'color' => $a, 'full_width' => true],
        ];
    }
}
