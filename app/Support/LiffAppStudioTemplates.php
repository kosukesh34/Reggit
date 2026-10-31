<?php

namespace App\Support;

use App\Support\LiffAppStudio\Layouts\LoyaltyLayouts;
use App\Support\LiffAppStudio\Layouts\OrderLayouts;
use App\Support\LiffAppStudio\Screens;
use App\Support\LiffAppStudio\Suites;

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
    public const MAIN_TABS = [
        'home', 'products', 'orders', 'stamps', 'coupons',
        'queue', 'reservations', 'member', 'profile', 'gallery', 'announcements',
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
                'stamp_screen_design' => 'wallet',
                'stamp_slot_style' => 'rounded',
                'stamp_filled_style' => 'solid',
                'coupon_screen_design' => 'apple',
                'reservation_screen_design' => 'apple',
                'activity_screen_design' => 'cards',
            ],
            'citrus' => [
                'accent_color' => '#A81612',
                'font_family' => 'sans',
                'surface_tone' => 'warm',
                'card_style' => 'elevated',
                'button_shape' => 'pill',
                'tab_style' => 'floating',
                'density' => 'compact',
                'background_style' => 'solid',
                'bg_color' => '#FDF8F2',
                'content_width' => 'edge',
                'radius_scale' => 'round',
                'system_ui_style' => 'compact',
                'system_header_style' => 'hero',
                'member_screen_design' => 'wallet',
                'stamp_screen_design' => 'wallet',
                'stamp_slot_style' => 'rounded',
                'stamp_filled_style' => 'solid',
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
                'stamp_screen_design' => 'circle_board',
                'stamp_slot_style' => 'circle',
                'stamp_filled_style' => 'icon',
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
                'stamp_screen_design' => 'grid_card',
                'stamp_slot_style' => 'rounded',
                'stamp_filled_style' => 'image',
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
                'stamp_screen_design' => 'minimal',
                'stamp_slot_style' => 'rounded',
                'stamp_filled_style' => 'icon',
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
                'stamp_screen_design' => 'wallet',
                'stamp_slot_style' => 'square',
                'stamp_filled_style' => 'solid',
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
                'stamp_screen_design' => 'minimal',
                'stamp_slot_style' => 'circle',
                'stamp_filled_style' => 'solid',
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
                'stamp_slot_style' => 'rounded',
                'stamp_filled_style' => 'image',
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
                'stamp_screen_design' => 'circle_board',
                'stamp_slot_style' => 'circle',
                'stamp_filled_style' => 'icon',
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
        $layouts = LoyaltyLayouts::layouts();
        $layouts['products'] = OrderLayouts::layouts()['products'];

        return $layouts;
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
        return array_merge(Suites::all(), Screens::all());
    }
}
