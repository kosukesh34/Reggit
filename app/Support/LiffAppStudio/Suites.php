<?php

namespace App\Support\LiffAppStudio;

use App\Support\LiffAppStudio\Layouts\AppointmentLayouts;
use App\Support\LiffAppStudio\Layouts\BookingLayouts;
use App\Support\LiffAppStudio\Layouts\CafeLayouts;
use App\Support\LiffAppStudio\Layouts\ClinicLayouts;
use App\Support\LiffAppStudio\Layouts\CounterLayouts;
use App\Support\LiffAppStudio\Layouts\DeliveryLayouts;
use App\Support\LiffAppStudio\Layouts\LandingLayouts;
use App\Support\LiffAppStudio\Layouts\LoyaltyLayouts;
use App\Support\LiffAppStudio\Layouts\OrderLayouts;
use App\Support\LiffAppStudio\Layouts\RetailLayouts;
use App\Support\LiffAppStudio\Layouts\SalonLayouts;
use App\Support\LiffAppStudio\Layouts\SimpleLayouts;
use App\Support\LiffAppStudio\Layouts\SweetsLayouts;
use App\Support\LiffAppStudioTemplates;

/** App Studio の業種別スイート（全タブのレイアウト・メニュー・テーマ一式）。 */
final class Suites
{
    /** 業種 → タブ別レイアウト */
    private const INDUSTRY_LAYOUTS = [
        'simple' => SimpleLayouts::class,
        'counter' => CounterLayouts::class,
        'appointment' => AppointmentLayouts::class,
        'loyalty' => LoyaltyLayouts::class,
        'order' => OrderLayouts::class,
        'booking' => BookingLayouts::class,
        'cafe' => CafeLayouts::class,
        'landing' => LandingLayouts::class,
        'salon' => SalonLayouts::class,
        'retail' => RetailLayouts::class,
        'sweets' => SweetsLayouts::class,
        'clinic' => ClinicLayouts::class,
        'delivery' => DeliveryLayouts::class,
    ];

    /** @return array<int, array<string, mixed>> */
    public static function all(): array
    {
        return [
            self::suiteEntry(
                'rg-suite-loyalty',
                '会員OS',
                'loyalty',
                'Starbucks Rewards 風。挨拶→残高と次特典までの距離→特典交換CTA→会員証提示。ホームに商品棚は置かない。',
                [
                    'theme_preset' => 'store',
                    'accent_color' => '#00754A',
                    'member_card_design' => 'emblem',
                    'member_screen_design' => 'wallet',
                    'stamp_screen_design' => 'grid_card',
                    'stamp_slot_style' => 'circle',
                    'stamp_filled_style' => 'image',
                    'coupon_screen_design' => 'ticket',
                    'density' => 'default',
                    'content_width' => 'comfy',
                    'card_style' => 'flat',
                    'button_shape' => 'pill',
                    'surface_tone' => 'sage',
                    'bg_color' => '#F4F8F6',
                    'app_display_name' => '〇〇店',
                ],
                self::menu(['home', 'stamps', 'coupons', ['member', '会員証', 'fa-barcode'], 'announcements']),
                ''
            ),
            self::suiteEntry(
                'rg-suite-order',
                '注文OS',
                'order',
                'バーガーキング／マクド風。受取カード最上部→プロモヒーロー→クーポン2列→注文CTA。会員は下部タブに置く。',
                [
                    'theme_preset' => 'citrus',
                    'accent_color' => '#A81612',
                    'density' => 'compact',
                    'content_width' => 'edge',
                    'button_shape' => 'pill',
                    'tab_style' => 'floating',
                    'surface_tone' => 'warm',
                    'bg_color' => '#FDF8F2',
                    'coupon_screen_design' => 'ticket',
                    'stamp_screen_design' => 'wallet',
                    'stamp_slot_style' => 'rounded',
                    'stamp_filled_style' => 'solid',
                    'app_display_name' => '〇〇キッチン',
                ],
                self::menu([
                    'home',
                    'coupons',
                    ['products', 'オーダー', 'fa-utensils'],
                    ['member', 'メンバー', 'fa-crown'],
                    'orders',
                ]),
                '.theme-citrus .builder-collection__grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;}'
            ),
            self::suiteEntry(
                'rg-suite-booking',
                '予約OS',
                'booking',
                'ホットペッパー風。次回予約カード→空き枠ヒーロー→再予約ワンタップ。ホームに履歴一覧は置かない。',
                [
                    'theme_preset' => 'lavender',
                    'accent_color' => '#af52de',
                    'font_family' => 'rounded',
                    'density' => 'relaxed',
                    'content_width' => 'narrow',
                    'card_style' => 'elevated',
                    'stamp_screen_design' => 'circle_board',
                    'stamp_slot_style' => 'circle',
                    'stamp_filled_style' => 'icon',
                    'app_display_name' => '〇〇サロン',
                ],
                self::menu(['home', ['reservations', '予約', 'fa-calendar-check'], 'products', 'gallery', 'member'])
            ),
            self::suiteEntry(
                'rg-suite-counter-operations',
                '店舗運営・注文カウンター',
                'counter',
                '注文、現在の待ち組数、最短受取時間を最上部に集約。大きな2ボタンからモバイルオーダーと順番待ちへ分岐する、日々の店舗運営に強い構成。',
                [
                    'theme_preset' => 'citrus',
                    'accent_color' => '#151515',
                    'bg_color' => '#F2F0E9',
                    'surface_tone' => 'warm',
                    'density' => 'compact',
                    'content_width' => 'edge',
                    'card_style' => 'outline',
                    'button_shape' => 'rounded',
                    'tab_style' => 'default',
                    'system_header_style' => 'minimal',
                    'stamp_screen_design' => 'wallet',
                    'stamp_slot_style' => 'rounded',
                    'stamp_filled_style' => 'solid',
                    'app_display_name' => '〇〇カウンター',
                ],
                self::menu([
                    'home',
                    ['products', '注文', 'fa-bag-shopping'],
                    ['queue', '順番待ち', 'fa-users'],
                    ['member', '会員証', 'fa-id-card'],
                    ['orders', '受取状況', 'fa-receipt'],
                ]),
                '.builder-quick-actions--counter{grid-template-columns:1.15fr .85fr;}.builder-quick-actions--counter .builder-action{min-height:132px;align-items:flex-end;}'
            ),
            self::suiteEntry(
                'rg-suite-appointment-operations',
                '店舗運営・予約スタジオ',
                'appointment',
                '次回予約をホームの主役にし、直近の空き枠を横並びで提示。予約確認・新規予約・会員証を少ない操作で使える来店運営向け構成。',
                [
                    'theme_preset' => 'store',
                    'accent_color' => '#203F3A',
                    'bg_color' => '#F5F3EE',
                    'surface_tone' => 'sage',
                    'font_family' => 'serif-display',
                    'density' => 'relaxed',
                    'content_width' => 'narrow',
                    'card_style' => 'elevated',
                    'button_shape' => 'pill',
                    'tab_style' => 'floating',
                    'system_header_style' => 'minimal',
                    'stamp_screen_design' => 'circle_board',
                    'stamp_slot_style' => 'circle',
                    'stamp_filled_style' => 'icon',
                    'app_display_name' => '〇〇スタジオ',
                ],
                self::menu([
                    'home',
                    ['reservations', '予約', 'fa-calendar-check'],
                    ['member', '会員証', 'fa-id-card'],
                    ['coupons', 'ご優待', 'fa-ticket-alt'],
                    ['gallery', 'スタイル', 'fa-images'],
                ]),
                '.builder-card--next-appointment{border-radius:26px;}.builder-quick-actions--slots{grid-template-columns:repeat(3,1fr);}'
            ),
            self::suiteEntry(
                'rg-suite-cafe',
                'カフェ専用',
                'cafe',
                'Starbucks 公式アプリ風。挨拶＋リワード→モバイルオーダー主役カード→会員証/スタンプ。ホームに商品棚は置かない。',
                [
                    'theme_preset' => 'sand',
                    'accent_color' => '#00754A',
                    'surface_tone' => 'sage',
                    'bg_color' => '#FFFFFF',
                    'member_card_design' => 'emblem',
                    'member_screen_design' => 'wallet',
                    'stamp_screen_design' => 'circle_board',
                    'stamp_slot_style' => 'circle',
                    'stamp_filled_style' => 'image',
                    'coupon_screen_design' => 'ticket',
                    'button_shape' => 'pill',
                    'tab_style' => 'pill',
                    'app_display_name' => '〇〇カフェ',
                ],
                self::menu([
                    'home',
                    ['member', '会員証', 'fa-credit-card'],
                    ['products', 'オーダー', 'fa-mug-hot'],
                    'stamps',
                    'orders',
                ])
            ),
            self::suiteEntry(
                'rg-suite-landing',
                'Reggit LPスタイル',
                'landing',
                'Reggitの公開LPと同じセージグリーンの空気感。大きな写真と余白で、モバイルオーダー・会員証・順番待ちをわかりやすく見せるブランド型アプリ。',
                [
                    'theme_preset' => 'store',
                    'accent_color' => '#0E7C66',
                    'font_family' => 'sans',
                    'surface_tone' => 'sage',
                    'bg_color' => '#F1F5F4',
                    'content_width' => 'comfy',
                    'card_style' => 'flat',
                    'button_shape' => 'pill',
                    'tab_style' => 'floating',
                    'density' => 'relaxed',
                    'radius_scale' => 'soft',
                    'system_ui_style' => 'refined',
                    'system_header_style' => 'minimal',
                    'member_card_design' => 'emblem',
                    'member_screen_design' => 'wallet',
                    'stamp_screen_design' => 'grid_card',
                    'stamp_slot_style' => 'circle',
                    'stamp_filled_style' => 'image',
                    'coupon_screen_design' => 'ticket',
                    'reservation_screen_design' => 'apple',
                    'activity_screen_design' => 'cards',
                    'app_display_name' => '〇〇店',
                ],
                self::menu([
                    'home',
                    ['products', 'オーダー', 'fa-utensils'],
                    ['member', '会員証', 'fa-id-card'],
                    ['queue', '順番待ち', 'fa-users'],
                    ['coupons', '特典', 'fa-ticket-alt'],
                ]),
                'body.theme-store .builder-hero--editorial{min-height:300px;border-radius:0;box-shadow:none;}'
                .'body.theme-store .builder-hero--editorial .builder-hero__content{min-height:300px;justify-content:flex-end;text-align:left;}'
                .'body.theme-store .builder-editorial-card--overlay{border-radius:0;}'
            ),
            self::suiteEntry(
                'rg-suite-simple-rewards',
                'シンプル会員証',
                'simple',
                '会員証・クーポン・スタンプだけに絞った最小構成。写真や装飾を使わず、初めての方でも迷わない4タブの会員アプリ。',
                [
                    'theme_preset' => 'apple_system',
                    'accent_color' => '#171717',
                    'font_family' => 'sans',
                    'surface_tone' => 'ios',
                    'bg_color' => '#F7F7F5',
                    'content_width' => 'narrow',
                    'card_style' => 'outline',
                    'button_shape' => 'rounded',
                    'tab_style' => 'default',
                    'density' => 'compact',
                    'radius_scale' => 'default',
                    'system_ui_style' => 'compact',
                    'system_header_style' => 'minimal',
                    'member_card_design' => 'barcode',
                    'member_screen_design' => 'minimal',
                    'stamp_screen_design' => 'minimal',
                    'stamp_slot_style' => 'rounded',
                    'stamp_filled_style' => 'solid',
                    'coupon_screen_design' => 'compact',
                    'app_display_name' => 'SIMPLE PASS',
                ],
                self::menu([
                    'home',
                    ['member', '会員証', 'fa-barcode'],
                    ['coupons', 'クーポン', 'fa-ticket-alt'],
                    ['stamps', 'スタンプ', 'fa-stamp'],
                ]),
                'body.theme-apple_system{letter-spacing:0;}body.theme-apple_system .builder-card{box-shadow:none;}'
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
                    'stamp_screen_design' => 'minimal',
                    'stamp_slot_style' => 'rounded',
                    'stamp_filled_style' => 'icon',
                    'app_display_name' => '〇〇ヘア',
                ],
                self::menu(['home', ['gallery', 'スタイル', 'fa-images'], ['reservations', '予約', 'fa-calendar-check'], 'products', 'member']),
                '.builder-heading__title,.builder-hero__title{font-family:"Playfair Display",Georgia,"Times New Roman",serif;letter-spacing:.01em;}'
            ),
            self::suiteEntry(
                'rg-suite-retail',
                '小売ショップ',
                'retail',
                'UNIQLO 風。検索・スキャン導線とカテゴリ発見を先に置き、会員証は下部タブと sticky で常に手が届く。',
                [
                    'theme_preset' => 'noir',
                    'accent_color' => '#111111',
                    'content_width' => 'edge',
                    'card_style' => 'outline',
                    'system_ui_style' => 'refined',
                    'stamp_screen_design' => 'wallet',
                    'stamp_slot_style' => 'square',
                    'stamp_filled_style' => 'solid',
                    'app_display_name' => '〇〇ストア',
                ],
                self::menu(['home', ['products', '商品', 'fa-shopping-bag'], 'coupons', ['gallery', 'ルックブック', 'fa-images'], ['member', '会員証', 'fa-barcode'], 'announcements']),
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
                    'stamp_screen_design' => 'minimal',
                    'stamp_slot_style' => 'circle',
                    'stamp_filled_style' => 'solid',
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
                    'stamp_screen_design' => 'grid_card',
                    'stamp_slot_style' => 'rounded',
                    'stamp_filled_style' => 'solid',
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
                    'stamp_screen_design' => 'grid_card',
                    'stamp_slot_style' => 'rounded',
                    'stamp_filled_style' => 'image',
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
        $categoryMap = [
            'loyalty' => 'loyalty',
            'order' => 'order',
            'booking' => 'booking',
            'cafe' => 'cafe',
            'landing' => 'story',
            'simple' => 'loyalty',
            'counter' => 'order',
            'appointment' => 'booking',
            'salon' => 'salon',
            'retail' => 'retail',
            'clinic' => 'clinic',
            'delivery' => 'delivery',
            'sweets' => 'sweets',
        ];
        $theme = LiffAppStudioTemplates::theme($themeOver);
        // タブ背景 + アプリ全体(_global)を同一ペイロードに載せ、プレビュー/適用が実データをそのまま読めるようにする
        $tabSettings = LiffAppStudioTemplates::emptyTabs();
        $tabSettings['_global'] = $theme;

        return [
            'id' => $id,
            'name' => $name,
            'scope' => 'all_tabs',
            'category' => $categoryMap[$industry] ?? 'suite',
            'description' => $description,
            'target_tabs' => LiffAppStudioTemplates::MAIN_TABS,
            'menu' => $menu,
            'device_support' => ['line', 'ios', 'android'],
            'layouts' => self::INDUSTRY_LAYOUTS[$industry]::layouts(),
            'tab_settings' => $tabSettings,
            'custom_css' => $customCss,
            'theme' => $theme,
        ];
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
        'profile' => 'プロフィール',
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
        'profile' => 'fa-user',
        'gallery' => 'fa-images',
        'announcements' => 'fa-bullhorn',
    ];
}
