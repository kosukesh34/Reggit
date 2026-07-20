<?php

namespace App\Support;

use InvalidArgumentException;

class LiffDesignerSanitizer
{
    private const ALLOWED_TABS = [
        'home', 'products', 'orders', 'stamps', 'coupons',
        'queue', 'reservations', 'member', 'gallery', 'announcements',
    ];

    private const ALLOWED_BLOCK_TYPES = [
        'hero', 'heading', 'text', 'image', 'button', 'card', 'collection',
        'slider', 'icon_text', 'list', 'banner', 'spacer', 'divider', 'data',
        'member_summary', 'quick_actions', 'friend_add',
        'hours', 'access', 'social_links', 'faq', 'video', 'button_group',
        'stats_row',
    ];

    private const FRIEND_ADD_DESIGNS = ['solid', 'outline', 'soft'];

    /** タブメニュー（表示/非表示・並び替え・名前/アイコン変更）の対象タブ */
    private const MENU_TABS = [
        'home', 'products', 'orders', 'stamps', 'coupons',
        'queue', 'reservations', 'member', 'gallery', 'announcements',
    ];

    /** 外部Webページタブ（管理者がURLを設定するカスタムタブ）のキー */
    public const LINK_MENU_TABS = ['link_1', 'link_2', 'link_3'];

    /** 外部Webページタブの表示方法 */
    private const LINK_MENU_OPEN_MODES = ['embed', 'external'];

    /** 詳細ページレイアウト（タブと並列） */
    private const ALLOWED_DETAIL_KEYS = [
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
    ];

    private const SOCIAL_LINK_TYPES = [
        'instagram', 'x', 'facebook', 'youtube', 'tiktok', 'line', 'website',
    ];

    private const ALLOWED_DATA_SOURCES = [
        'products', 'orders', 'coupons', 'stamps', 'announcements', 'announcement_detail',
        'product_detail', 'coupon_detail', 'gallery_detail', 'stamp_detail',
        'queue', 'reservations', 'gallery', 'member_card', 'member',
    ];

    private const MEMBER_SUMMARY_DESIGNS = [
        'rewards', 'wallet', 'compact', 'emblem', 'minimal', 'split', 'glass', 'tier',
    ];

    private const STATS_ROW_FIELDS = [
        'static', 'points', 'rank', 'points_to_next',
    ];

    private const MEMBER_CARD_DESIGNS = [
        'default', 'logo_top', 'logo_center', 'logo_header',
        'compact', 'split_banner', 'minimal_line', 'emblem',
    ];

    private const HERO_DESIGNS = ['editorial', 'minimal', 'split', 'centered', 'apple'];

    private const HEADING_DESIGNS = ['plain', 'section', 'eyebrow', 'underline'];

    private const TEXT_DESIGNS = ['body', 'lead', 'note', 'quote'];

    private const IMAGE_DESIGNS = ['rounded', 'full_bleed', 'polaroid', 'arch'];

    private const BANNER_DESIGNS = ['promo', 'outline', 'soft', 'pill', 'countdown'];

    private const CARD_DESIGNS = ['editorial', 'horizontal', 'overlay', 'featured'];

    private const BUTTON_DESIGNS = ['cta', 'outline', 'soft', 'sticky'];

    private const QUICK_ACTIONS_DESIGNS = ['grid', 'pill', 'compact'];

    private const COLLECTION_DESIGNS = ['product_shelf', 'editorial', 'compact', 'magazine', 'chip_scroll', 'picks'];

    private const SLIDER_DESIGNS = ['hero', 'cards', 'minimal'];

    private const STORE_CARD_DESIGNS = ['table', 'card', 'timeline'];

    private const ACCESS_DESIGNS = ['card', 'map', 'minimal'];

    private const FAQ_DESIGNS = ['accordion', 'cards', 'minimal'];

    private const DATA_DESIGNS = ['cards', 'timeline', 'compact', 'premium'];

    private const STAMP_SCREEN_DESIGNS = ['grid_card', 'wallet', 'circle_board', 'minimal'];

    private const STAMP_SLOT_STYLES = ['circle', 'rounded', 'square'];

    private const STAMP_FILLED_STYLES = ['image', 'solid', 'icon'];

    private const MEMBER_CARD_BG_STYLES = ['solid', 'gradient', 'image'];

    public static function sanitizeLayouts(array $layouts): array
    {
        $safe = [];
        foreach (self::ALLOWED_TABS as $tab) {
            $rows = $layouts[$tab] ?? [];
            $safe[$tab] = [];
            if (! is_array($rows)) {
                continue;
            }
            $rows = array_slice($rows, 0, 100);
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $block = self::sanitizeBlock($row);
                if ($block !== null) {
                    $safe[$tab][] = $block;
                }
            }
        }

        return $safe;
    }

    /**
     * @param  array<string, mixed>  $layouts
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function sanitizeDetailLayouts(array $layouts): array
    {
        $safe = [];
        foreach (self::ALLOWED_DETAIL_KEYS as $key) {
            $rows = $layouts[$key] ?? [];
            $safe[$key] = [];
            if (! is_array($rows)) {
                continue;
            }
            $rows = array_slice($rows, 0, 100);
            foreach ($rows as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $block = self::sanitizeBlock($row);
                if ($block !== null) {
                    $safe[$key][] = $block;
                }
            }
        }

        return $safe;
    }

    public static function sanitizeTabSettings(?array $tabSettings): ?array
    {
        if ($tabSettings === null) {
            return null;
        }

        $safe = [];

        $global = $tabSettings['_global'] ?? null;
        if (is_array($global)) {
            $globalSafe = self::sanitizeGlobalSettings($global);
            if ($globalSafe !== []) {
                $safe['_global'] = $globalSafe;
            }
        }

        $screenKeys = array_merge(self::ALLOWED_TABS, self::ALLOWED_DETAIL_KEYS);
        foreach ($screenKeys as $tab) {
            $row = $tabSettings[$tab] ?? null;
            if (! is_array($row)) {
                continue;
            }
            $item = [];
            $bgColor = self::color($row['background_color'] ?? null);
            if ($bgColor !== null) {
                $item['background_color'] = $bgColor;
            }
            $bgImage = self::safeUrl($row['background_image'] ?? null, false);
            if ($bgImage !== '') {
                $item['background_image'] = $bgImage;
            }
            if (isset($row['content_padding']) && is_numeric($row['content_padding'])) {
                $item['content_padding'] = self::num($row['content_padding'], 8, 40, 18);
            }
            $bgSize = (string) ($row['background_size'] ?? '');
            if (in_array($bgSize, ['cover', 'contain', 'auto'], true)) {
                $item['background_size'] = $bgSize;
            }
            $bgPosition = self::text($row['background_position'] ?? '', 32);
            if (in_array($bgPosition, ['center', 'top', 'bottom', 'left', 'right'], true)) {
                $item['background_position'] = $bgPosition;
            }
            $bgRepeat = (string) ($row['background_repeat'] ?? '');
            if (in_array($bgRepeat, ['no-repeat', 'repeat', 'repeat-x', 'repeat-y'], true)) {
                $item['background_repeat'] = $bgRepeat;
            }
            if ($item !== []) {
                $safe[$tab] = $item;
            }
        }

        return $safe;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, bool|int|string>
     */
    private static function sanitizeGlobalSettings(array $row): array
    {
        $item = [];
        if (isset($row['content_padding']) && is_numeric($row['content_padding'])) {
            $item['content_padding'] = self::num($row['content_padding'], 8, 40, 18);
        }
        if (isset($row['section_gap']) && is_numeric($row['section_gap'])) {
            $item['section_gap'] = self::num($row['section_gap'], 4, 32, 16);
        }
        $radiusScale = (string) ($row['radius_scale'] ?? 'default');
        if (in_array($radiusScale, ['compact', 'default', 'soft', 'round'], true)) {
            $item['radius_scale'] = $radiusScale;
        }
        $tabStyle = (string) ($row['tab_style'] ?? 'default');
        if (in_array($tabStyle, ['default', 'pill', 'floating'], true)) {
            $item['tab_style'] = $tabStyle;
        }

        // ===== SaaS テーマプリセット =====
        $themePreset = (string) ($row['theme_preset'] ?? '');
        $validPresetIds = array_map(static fn ($p) => (string) ($p['id'] ?? ''), LiffThemePresets::all());
        if ($themePreset === 'custom' || in_array($themePreset, $validPresetIds, true)) {
            $item['theme_preset'] = $themePreset;
        }

        $accent = self::color($row['accent_color'] ?? null);
        if ($accent !== null) {
            $item['accent_color'] = $accent;
        }

        $fontFamily = (string) ($row['font_family'] ?? '');
        if (array_key_exists($fontFamily, LiffThemePresets::fontStacks())) {
            $item['font_family'] = $fontFamily;
        }

        $surfaceTone = (string) ($row['surface_tone'] ?? '');
        if (array_key_exists($surfaceTone, LiffThemePresets::surfaceTones())) {
            $item['surface_tone'] = $surfaceTone;
        }

        $cardStyle = (string) ($row['card_style'] ?? '');
        if (in_array($cardStyle, ['flat', 'elevated', 'outline'], true)) {
            $item['card_style'] = $cardStyle;
        }

        $buttonShape = (string) ($row['button_shape'] ?? '');
        if (in_array($buttonShape, ['rounded', 'pill', 'square'], true)) {
            $item['button_shape'] = $buttonShape;
        }

        $density = (string) ($row['density'] ?? '');
        if (in_array($density, ['compact', 'default', 'relaxed'], true)) {
            $item['density'] = $density;
        }

        $systemUiStyle = (string) ($row['system_ui_style'] ?? '');
        if (in_array($systemUiStyle, ['refined', 'editorial', 'compact', 'luxe'], true)) {
            $item['system_ui_style'] = $systemUiStyle;
        }

        $systemCardStyle = (string) ($row['system_card_style'] ?? '');
        if (in_array($systemCardStyle, ['soft', 'elevated', 'outline', 'flat'], true)) {
            $item['system_card_style'] = $systemCardStyle;
        }

        $systemMediaShape = (string) ($row['system_media_shape'] ?? '');
        if (in_array($systemMediaShape, ['rounded', 'square', 'arch'], true)) {
            $item['system_media_shape'] = $systemMediaShape;
        }

        $systemHeaderStyle = (string) ($row['system_header_style'] ?? '');
        if (in_array($systemHeaderStyle, ['minimal', 'editorial', 'hero'], true)) {
            $item['system_header_style'] = $systemHeaderStyle;
        }

        if (isset($row['require_membership'])) {
            $item['require_membership'] = filter_var($row['require_membership'], FILTER_VALIDATE_BOOLEAN);
        }

        $memberCardDesign = (string) ($row['member_card_design'] ?? '');
        if ($memberCardDesign === '' || in_array($memberCardDesign, self::MEMBER_CARD_DESIGNS, true)) {
            $item['member_card_design'] = $memberCardDesign;
        }

        $memberCardDisplayType = (string) ($row['member_card_display_type'] ?? '');
        if (in_array($memberCardDisplayType, ['card', 'sheet'], true)) {
            $item['member_card_display_type'] = $memberCardDisplayType;
        }

        $memberCardCodeType = (string) ($row['member_card_code_type'] ?? '');
        if (in_array($memberCardCodeType, ['barcode', 'qrcode'], true)) {
            $item['member_card_code_type'] = $memberCardCodeType;
        }

        $memberCardAccent = self::color($row['member_card_accent_color'] ?? null);
        if ($memberCardAccent !== null) {
            $item['member_card_accent_color'] = $memberCardAccent;
        }

        $memberCardBgStyle = (string) ($row['member_card_bg_style'] ?? '');
        if (in_array($memberCardBgStyle, self::MEMBER_CARD_BG_STYLES, true)) {
            $item['member_card_bg_style'] = $memberCardBgStyle;
        }

        $stampScreenDesign = (string) ($row['stamp_screen_design'] ?? '');
        if (in_array($stampScreenDesign, self::STAMP_SCREEN_DESIGNS, true)) {
            $item['stamp_screen_design'] = $stampScreenDesign;
        }

        $stampSlotStyle = (string) ($row['stamp_slot_style'] ?? '');
        if (in_array($stampSlotStyle, self::STAMP_SLOT_STYLES, true)) {
            $item['stamp_slot_style'] = $stampSlotStyle;
        }

        $stampFilledStyle = (string) ($row['stamp_filled_style'] ?? '');
        if (in_array($stampFilledStyle, self::STAMP_FILLED_STYLES, true)) {
            $item['stamp_filled_style'] = $stampFilledStyle;
        }

        $stampAccent = self::color($row['stamp_accent_color'] ?? null);
        if ($stampAccent !== null) {
            $item['stamp_accent_color'] = $stampAccent;
        }

        $appDisplayName = trim((string) ($row['app_display_name'] ?? ''));
        if ($appDisplayName !== '') {
            $item['app_display_name'] = mb_substr($appDisplayName, 0, 80);
        }

        $memberScreenDesign = (string) ($row['member_screen_design'] ?? '');
        if (in_array($memberScreenDesign, ['wallet', 'premium', 'minimal', 'compact', 'apple', 'glass'], true)) {
            $item['member_screen_design'] = $memberScreenDesign;
        }

        $couponScreenDesign = (string) ($row['coupon_screen_design'] ?? '');
        if (in_array($couponScreenDesign, ['ticket', 'gallery', 'compact', 'luxury', 'apple', 'glass'], true)) {
            $item['coupon_screen_design'] = $couponScreenDesign;
        }

        $reservationScreenDesign = (string) ($row['reservation_screen_design'] ?? '');
        if (in_array($reservationScreenDesign, ['cards', 'timeline', 'compact', 'concierge', 'apple', 'glass'], true)) {
            $item['reservation_screen_design'] = $reservationScreenDesign;
        }

        $activityScreenDesign = (string) ($row['activity_screen_design'] ?? '');
        if (in_array($activityScreenDesign, ['cards', 'timeline', 'compact', 'dashboard'], true)) {
            $item['activity_screen_design'] = $activityScreenDesign;
        }

        $bgStyle = (string) ($row['background_style'] ?? '');
        if (array_key_exists($bgStyle, LiffThemePresets::backgroundStyles())) {
            $item['background_style'] = $bgStyle;
        }

        $bgColor = self::color($row['bg_color'] ?? null);
        if ($bgColor !== null) {
            $item['bg_color'] = $bgColor;
        }

        $gradStart = self::color($row['bg_gradient_start'] ?? null);
        if ($gradStart !== null) {
            $item['bg_gradient_start'] = $gradStart;
        }

        $gradEnd = self::color($row['bg_gradient_end'] ?? null);
        if ($gradEnd !== null) {
            $item['bg_gradient_end'] = $gradEnd;
        }

        $bgImage = self::safeUrl($row['bg_image_url'] ?? null, false);
        if ($bgImage !== '' && str_starts_with($bgImage, 'https://')) {
            $item['bg_image_url'] = $bgImage;
        }

        if (isset($row['bg_overlay_opacity']) && is_numeric($row['bg_overlay_opacity'])) {
            $item['bg_overlay_opacity'] = self::num($row['bg_overlay_opacity'], 0, 80, 60);
        }

        $contentWidth = (string) ($row['content_width'] ?? '');
        if (array_key_exists($contentWidth, LiffThemePresets::contentWidths())) {
            $item['content_width'] = $contentWidth;
        }

        return $item;
    }

    /**
     * タブメニュー項目（表示/非表示・並び替え・名前/アイコン変更）をサニタイズする。
     * frontend_menu_items と同じ構造（tab / label / icon / order / enabled）を返す。
     *
     * @param  array<int, mixed>  $items
     * @return array<int, array{tab: string, label: string, icon: string, order: int, enabled: bool}>
     */
    public static function sanitizeMenuItems(array $items): array
    {
        $safe = [];
        $seen = [];
        $order = 0;
        foreach ($items as $row) {
            if (! is_array($row)) {
                continue;
            }
            $tab = (string) ($row['tab'] ?? '');
            $isLinkTab = in_array($tab, self::LINK_MENU_TABS, true);
            if ((! in_array($tab, self::MENU_TABS, true) && ! $isLinkTab) || isset($seen[$tab])) {
                continue;
            }

            $label = self::text($row['label'] ?? '', 12);
            $icon = (string) ($row['icon'] ?? '');
            if (preg_match('/^fa-[a-z0-9-]{1,40}$/i', $icon) !== 1) {
                $icon = '';
            }

            $item = [
                'tab' => $tab,
                'label' => $label,
                'icon' => $icon,
                'order' => $order,
                'enabled' => ! isset($row['enabled']) || $row['enabled'] !== false,
            ];

            if ($isLinkTab) {
                $url = self::text($row['url'] ?? '', 1000);
                if (preg_match('/^https:\/\//i', $url) !== 1) {
                    continue;
                }
                $item['url'] = $url;
                $item['open'] = 'embed';
                $item['label'] = $label !== '' ? $label : 'リンク';
                $item['icon'] = $icon !== '' ? $icon : 'fa-globe';
            }

            $seen[$tab] = true;
            $safe[] = $item;
            $order++;
        }

        return $safe;
    }

    public static function sanitizeCustomCss(string $css): string
    {
        $css = str_replace("\0", '', $css);
        $css = mb_substr($css, 0, 20000);
        $css = preg_replace('/<\/style/i', '<\\/style', $css) ?? '';

        $denyPatterns = [
            '/@import/i',
            '/expression\s*\(/i',
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/-moz-binding/i',
            '/behavior\s*:/i',
        ];
        foreach ($denyPatterns as $pattern) {
            if (preg_match($pattern, $css) === 1) {
                throw new InvalidArgumentException('カスタムCSSに使用できない記述が含まれています。');
            }
        }

        return trim($css);
    }

    private static function sanitizeBlock(array $row): ?array
    {
        $type = (string) ($row['type'] ?? 'text');
        if ($type === 'label') {
            $type = 'heading';
        }
        if ($type === 'gallery') {
            $type = 'data';
            $row['source'] = 'gallery';
        }
        if ($type === 'form') {
            $type = 'text';
            $row['text'] = $row['body'] ?? ($row['text'] ?? '');
        }

        if (! in_array($type, self::ALLOWED_BLOCK_TYPES, true)) {
            return null;
        }

        $b = ['type' => $type];
        if (isset($row['id']) && is_string($row['id']) && preg_match('/^[a-zA-Z0-9_-]{1,64}$/', $row['id']) === 1) {
            $b['id'] = $row['id'];
        }

        $b['border_radius'] = self::num($row['border_radius'] ?? 10, 0, 24, 10);
        $b['padding'] = self::num($row['padding'] ?? 0, 0, 48, 0);
        $b['margin_bottom'] = self::num($row['margin_bottom'] ?? 0, 0, 48, 0);
        $b['opacity'] = self::num($row['opacity'] ?? 100, 20, 100, 100);
        $b['shadow'] = (bool) ($row['shadow'] ?? false);
        $align = (string) ($row['text_align'] ?? 'left');
        $b['text_align'] = in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';
        $bg = self::color($row['background_color'] ?? null);
        if ($bg !== null) {
            $b['background_color'] = $bg;
        }
        $textColor = self::color($row['text_color'] ?? null);
        if ($textColor !== null) {
            $b['text_color'] = $textColor;
        }

        switch ($type) {
            case 'hero':
                $b['title'] = self::text($row['title'] ?? 'ようこそ！', 80);
                $b['subtitle'] = self::text($row['subtitle'] ?? ($row['text'] ?? ''), 160);
                $b['image_url'] = self::safeUrl($row['image_url'] ?? null, false);
                $b['gradient_start'] = self::color($row['gradient_start'] ?? '#667eea') ?? '#667eea';
                $b['gradient_end'] = self::color($row['gradient_end'] ?? '#764ba2') ?? '#764ba2';
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::HERO_DESIGNS, 'editorial');
                break;
            case 'heading':
                $b['text'] = self::text($row['text'] ?? ($row['title'] ?? '見出し'), 120);
                $size = (string) ($row['size'] ?? 'medium');
                $b['size'] = in_array($size, ['small', 'medium', 'large'], true) ? $size : 'medium';
                $b['text_color'] = self::color($row['text_color'] ?? '#1f2937') ?? '#1f2937';
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::HEADING_DESIGNS, 'plain');
                break;
            case 'text':
                $b['text'] = self::text($row['text'] ?? ($row['body'] ?? ''), 2000);
                $b['font_size'] = self::num($row['font_size'] ?? 13, 10, 28, 13);
                $b['text_color'] = self::color($row['text_color'] ?? '#4b5563') ?? '#4b5563';
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::TEXT_DESIGNS, 'body');
                break;
            case 'image':
                $b['image_url'] = self::safeUrl($row['image_url'] ?? null, false);
                $b['alt'] = self::text($row['alt'] ?? '', 120);
                $fit = (string) ($row['fit'] ?? 'cover');
                $b['fit'] = in_array($fit, ['cover', 'contain', 'fill'], true) ? $fit : 'cover';
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::IMAGE_DESIGNS, 'rounded');
                break;
            case 'button':
                $b['text'] = self::text($row['text'] ?? ($row['button_label'] ?? 'ボタン'), 60);
                $b['color'] = self::color($row['color'] ?? '#06C755') ?? '#06C755';
                $b['text_color'] = self::color($row['text_color'] ?? '#ffffff') ?? '#ffffff';
                $b['action'] = self::safeUrl($row['action'] ?? ($row['button_url'] ?? ''), true);
                $b['full_width'] = ! isset($row['full_width']) || (bool) $row['full_width'];
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::BUTTON_DESIGNS, 'cta');
                break;
            case 'card':
                $b['title'] = self::text($row['title'] ?? '本日のおすすめ', 80);
                $b['description'] = self::text($row['description'] ?? '', 240);
                $b['image_url'] = self::safeUrl($row['image_url'] ?? null, false);
                $b['image_height'] = self::num($row['image_height'] ?? 140, 60, 360, 140);
                $cardAction = (string) ($row['action'] ?? ($row['link'] ?? ''));
                if ($cardAction !== '') {
                    $safeCardAction = self::safeUrl($cardAction, true);
                    if ($safeCardAction !== '') {
                        $b['action'] = $safeCardAction;
                    }
                }
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::CARD_DESIGNS, 'editorial');
                break;
            case 'collection':
                $b['layout'] = ((string) ($row['layout'] ?? 'scroll')) === 'grid' ? 'grid' : 'scroll';
                $b['section_label'] = self::text($row['section_label'] ?? ($row['title'] ?? ''), 40);
                $itemAction = (string) ($row['item_action'] ?? '');
                if ($itemAction !== '') {
                    $safeItemAction = self::safeUrl($itemAction, true);
                    if ($safeItemAction !== '') {
                        $b['item_action'] = $safeItemAction;
                    }
                }
                $b['columns'] = self::num($row['columns'] ?? 2, 2, 4, 2);
                $b['max_rows'] = self::num($row['max_rows'] ?? 0, 0, 10, 0);
                $b['item_width'] = self::num($row['item_width'] ?? 140, 80, 280, 140);
                $b['item_image_height'] = self::num($row['item_image_height'] ?? 100, 40, 240, 100);
                $b['gap'] = self::num($row['gap'] ?? 8, 0, 32, 8);
                $b['show_description'] = ! isset($row['show_description']) || (bool) $row['show_description'];
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::COLLECTION_DESIGNS, 'product_shelf');
                $items = is_array($row['items'] ?? null) ? array_slice($row['items'], 0, 24) : [];
                $b['items'] = array_values(array_filter(array_map(function ($it) {
                    if (! is_array($it)) {
                        return null;
                    }
                    $item = [
                        'title' => self::text($it['title'] ?? '', 60),
                        'image_url' => self::safeUrl($it['image_url'] ?? null, false),
                        'description' => self::text($it['description'] ?? '', 120),
                        'badge' => self::text($it['badge'] ?? '', 16),
                    ];
                    $itemAction = self::safeUrl($it['action'] ?? '', true);
                    if ($itemAction !== '') {
                        $item['action'] = $itemAction;
                    }

                    return $item;
                }, $items)));
                break;
            case 'slider':
                $b['section_label'] = self::text($row['section_label'] ?? ($row['title'] ?? ''), 40);
                $b['slide_height'] = self::num($row['slide_height'] ?? 160, 80, 360, 160);
                $b['auto_play'] = ! isset($row['auto_play']) || (bool) $row['auto_play'];
                $b['show_dots'] = ! isset($row['show_dots']) || (bool) $row['show_dots'];
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::SLIDER_DESIGNS, 'hero');
                $sliderSource = (string) ($row['source'] ?? '');
                if ($sliderSource === 'gallery') {
                    $b['source'] = 'gallery';
                    $b['slides'] = [];
                    break;
                }
                $slides = is_array($row['slides'] ?? null) ? array_slice($row['slides'], 0, 12) : [];
                $b['slides'] = array_values(array_filter(array_map(function ($it) {
                    if (! is_array($it)) {
                        return null;
                    }

                    return [
                        'image_url' => self::safeUrl($it['image_url'] ?? null, false),
                        'caption' => self::text($it['caption'] ?? '', 100),
                    ];
                }, $slides)));
                break;
            case 'icon_text':
                $rows = is_array($row['rows'] ?? null) ? array_slice($row['rows'], 0, 12) : [];
                $b['rows'] = array_values(array_filter(array_map(function ($it) {
                    if (! is_array($it)) {
                        return null;
                    }
                    $icon = (string) ($it['icon'] ?? 'fa-check-circle');
                    if (preg_match('/^fa-[a-z0-9-]{1,40}$/i', $icon) !== 1) {
                        $icon = 'fa-check-circle';
                    }

                    return [
                        'icon' => $icon,
                        'text' => self::text($it['text'] ?? '', 80),
                        'color' => self::color($it['color'] ?? '#06C755') ?? '#06C755',
                    ];
                }, $rows)));
                break;
            case 'list':
                $style = (string) ($row['style'] ?? 'bullet');
                $b['style'] = in_array($style, ['bullet', 'number'], true) ? $style : 'bullet';
                $b['text_color'] = self::color($row['text_color'] ?? '#374151') ?? '#374151';
                $items = $row['items'] ?? ($row['list_items'] ?? []);
                $items = is_array($items) ? array_slice($items, 0, 30) : [];
                $b['items'] = array_map(fn ($v) => self::text($v, 120), $items);
                break;
            case 'banner':
                $icon = (string) ($row['icon'] ?? 'fa-info-circle');
                if (preg_match('/^fa-[a-z0-9-]{1,40}$/i', $icon) !== 1) {
                    $icon = 'fa-info-circle';
                }
                $b['icon'] = $icon;
                $b['text'] = self::text($row['text'] ?? '', 120);
                $b['color'] = self::color($row['color'] ?? '#3b82f6') ?? '#3b82f6';
                $b['text_color'] = self::color($row['text_color'] ?? '#ffffff') ?? '#ffffff';
                $action = (string) ($row['action'] ?? ($row['link'] ?? ''));
                if ($action !== '') {
                    $safeAction = self::safeUrl($action, true);
                    if ($safeAction !== '') {
                        $b['action'] = $safeAction;
                    }
                }
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::BANNER_DESIGNS, 'promo');
                $bannerField = self::statsRowField($row['field'] ?? null);
                if ($bannerField === 'points_to_next') {
                    $b['field'] = $bannerField;
                }
                break;
            case 'spacer':
                $b['height'] = self::num($row['height'] ?? 16, 4, 120, 16);
                break;
            case 'divider':
                $b['divider_color'] = self::color($row['divider_color'] ?? '#e5e7eb') ?? '#e5e7eb';
                $line = (string) ($row['divider_style'] ?? 'solid');
                $b['divider_style'] = in_array($line, ['solid', 'dashed', 'dotted'], true) ? $line : 'solid';
                break;
            case 'data':
                $src = (string) ($row['source'] ?? 'products');
                if ($src === 'member') {
                    $src = 'member_card';
                }
                $b['source'] = in_array($src, self::ALLOWED_DATA_SOURCES, true) ? $src : 'products';
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::DATA_DESIGNS, 'cards');
                $cardDesign = (string) ($row['card_design'] ?? '');
                if ($cardDesign !== '' && in_array($cardDesign, self::MEMBER_CARD_DESIGNS, true)) {
                    $b['card_design'] = $cardDesign;
                }
                $stampDesign = (string) ($row['stamp_design'] ?? '');
                if ($stampDesign !== '' && in_array($stampDesign, self::STAMP_SCREEN_DESIGNS, true)) {
                    $b['stamp_design'] = $stampDesign;
                }
                $stampSlotStyle = (string) ($row['stamp_slot_style'] ?? '');
                if (in_array($stampSlotStyle, self::STAMP_SLOT_STYLES, true)) {
                    $b['stamp_slot_style'] = $stampSlotStyle;
                }
                $stampFilledStyle = (string) ($row['stamp_filled_style'] ?? '');
                if (in_array($stampFilledStyle, self::STAMP_FILLED_STYLES, true)) {
                    $b['stamp_filled_style'] = $stampFilledStyle;
                }
                $stampAccent = self::color($row['stamp_accent_color'] ?? null);
                if ($stampAccent !== null) {
                    $b['stamp_accent_color'] = $stampAccent;
                }
                break;
            case 'member_summary':
                $b['greeting'] = self::text($row['greeting'] ?? '', 60);
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::MEMBER_SUMMARY_DESIGNS, 'rewards');
                break;
            case 'stats_row':
                $b['section_label'] = self::text($row['section_label'] ?? ($row['title'] ?? ''), 40);
                $items = is_array($row['items'] ?? null) ? array_slice($row['items'], 0, 6) : [];
                $b['items'] = array_values(array_filter(array_map(function ($it) {
                    if (! is_array($it)) {
                        return null;
                    }
                    $item = [
                        'label' => self::text($it['label'] ?? '', 40),
                        'value' => self::text($it['value'] ?? '', 40),
                        'hint' => self::text($it['hint'] ?? '', 40),
                    ];
                    $field = self::statsRowField($it['field'] ?? null);
                    if ($field !== 'static') {
                        $item['field'] = $field;
                    }

                    return $item;
                }, $items)));
                break;
            case 'quick_actions':
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::QUICK_ACTIONS_DESIGNS, 'grid');
                $actions = is_array($row['actions'] ?? null) ? array_slice($row['actions'], 0, 8) : [];
                $b['actions'] = array_values(array_filter(array_map(function ($it) {
                    if (! is_array($it)) {
                        return null;
                    }
                    $icon = (string) ($it['icon'] ?? 'fa-circle');
                    if (preg_match('/^fa-[a-z0-9-]{1,40}$/i', $icon) !== 1) {
                        $icon = 'fa-circle';
                    }
                    $label = self::text($it['label'] ?? '', 24);
                    if ($label === '') {
                        return null;
                    }

                    return [
                        'icon' => $icon,
                        'label' => $label,
                        'action' => self::safeUrl($it['action'] ?? '', true),
                    ];
                }, $actions)));
                break;
            case 'hours':
                $b['title'] = self::text($row['title'] ?? '営業時間', 40);
                $b['note'] = self::text($row['note'] ?? '', 120);
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::STORE_CARD_DESIGNS, 'table');
                $rows = is_array($row['rows'] ?? null) ? array_slice($row['rows'], 0, 10) : [];
                $b['rows'] = array_values(array_filter(array_map(function ($it) {
                    if (! is_array($it)) {
                        return null;
                    }
                    $day = self::text($it['day'] ?? '', 20);
                    $time = self::text($it['time'] ?? '', 40);
                    if ($day === '' && $time === '') {
                        return null;
                    }

                    return ['day' => $day, 'time' => $time];
                }, $rows)));
                break;
            case 'access':
                $b['title'] = self::text($row['title'] ?? '店舗情報', 40);
                $b['address'] = self::text($row['address'] ?? '', 160);
                $b['phone'] = self::sanitizePhone($row['phone'] ?? '');
                $b['map_url'] = self::safeUrl($row['map_url'] ?? '', false);
                $b['note'] = self::text($row['note'] ?? '', 160);
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::ACCESS_DESIGNS, 'card');
                break;
            case 'social_links':
                $links = is_array($row['links'] ?? null) ? array_slice($row['links'], 0, 7) : [];
                $b['links'] = array_values(array_filter(array_map(function ($it) {
                    if (! is_array($it)) {
                        return null;
                    }
                    $kind = (string) ($it['type'] ?? '');
                    if (! in_array($kind, self::SOCIAL_LINK_TYPES, true)) {
                        return null;
                    }
                    $url = self::safeUrl($it['url'] ?? '', false);
                    if ($url === '') {
                        return null;
                    }

                    return ['type' => $kind, 'url' => $url];
                }, $links)));
                break;
            case 'faq':
                $b['title'] = self::text($row['title'] ?? 'よくある質問', 40);
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::FAQ_DESIGNS, 'accordion');
                $items = is_array($row['items'] ?? null) ? array_slice($row['items'], 0, 12) : [];
                $b['items'] = array_values(array_filter(array_map(function ($it) {
                    if (! is_array($it)) {
                        return null;
                    }
                    $q = self::text($it['q'] ?? '', 120);
                    $a = self::text($it['a'] ?? '', 500);
                    if ($q === '') {
                        return null;
                    }

                    return ['q' => $q, 'a' => $a];
                }, $items)));
                break;
            case 'video':
                $b['youtube_id'] = self::sanitizeYoutubeId($row['youtube_id'] ?? '');
                $b['caption'] = self::text($row['caption'] ?? '', 100);
                break;
            case 'button_group':
                $buttons = is_array($row['buttons'] ?? null) ? array_slice($row['buttons'], 0, 3) : [];
                $b['buttons'] = array_values(array_filter(array_map(function ($it) {
                    if (! is_array($it)) {
                        return null;
                    }
                    $text = self::text($it['text'] ?? '', 24);
                    if ($text === '') {
                        return null;
                    }

                    return [
                        'text' => $text,
                        'action' => self::safeUrl($it['action'] ?? '', true),
                        'color' => self::color($it['color'] ?? null),
                        'variant' => in_array((string) ($it['variant'] ?? 'solid'), ['solid', 'outline'], true) ? (string) ($it['variant'] ?? 'solid') : 'solid',
                    ];
                }, $buttons)));
                break;
            case 'friend_add':
                $b['text'] = self::text($row['text'] ?? '友だち追加', 40);
                $b['color'] = self::color($row['color'] ?? '#06C755') ?? '#06C755';
                $b['text_color'] = self::color($row['text_color'] ?? '#ffffff') ?? '#ffffff';
                $friendUrl = self::safeUrl($row['url'] ?? '', false);
                if ($friendUrl !== '') {
                    $b['url'] = $friendUrl;
                }
                $b['design'] = self::pickDesign((string) ($row['design'] ?? ''), self::FRIEND_ADD_DESIGNS, 'solid');
                break;
        }

        return $b;
    }

    private static function num($v, int $min, int $max, int $default): int
    {
        if (is_numeric($v) === false) {
            return $default;
        }
        $n = (int) $v;
        if ($n < $min) {
            return $min;
        }
        if ($n > $max) {
            return $max;
        }

        return $n;
    }

    private static function text($v, int $max = 255): string
    {
        $s = is_scalar($v) ? (string) $v : '';
        $s = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s) ?? '';

        return trim(mb_substr($s, 0, $max));
    }

    private static function color($v): ?string
    {
        $s = self::text($v, 7);
        if ($s === '') {
            return null;
        }
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $s) !== 1) {
            return null;
        }

        return strtolower($s);
    }

    /** YouTube 動画ID（11文字の英数・-_）のみ許可。URLが渡されたらIDを抽出する */
    private static function sanitizeYoutubeId($value): string
    {
        $s = self::text($value, 200);
        if ($s === '') {
            return '';
        }
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $s) === 1) {
            return $s;
        }
        if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $s, $m) === 1) {
            return $m[1];
        }

        return '';
    }

    /** 電話番号（数字・ハイフン・+・スペースのみ） */
    private static function sanitizePhone($value): string
    {
        $s = self::text($value, 20);
        if ($s === '' || preg_match('/^[0-9+\-\s()]{3,20}$/', $s) !== 1) {
            return '';
        }

        return $s;
    }

    /** @param  list<string>  $allowed */
    private static function pickDesign(string $value, array $allowed, string $default): string
    {
        return in_array($value, $allowed, true) ? $value : $default;
    }

    /** @param mixed $value */
    private static function statsRowField($value): string
    {
        $field = is_scalar($value) ? (string) $value : '';
        if ($field === '' || ! in_array($field, self::STATS_ROW_FIELDS, true)) {
            return 'static';
        }

        return $field;
    }

    private static function safeUrl($url, bool $allowAnchor): string
    {
        $u = self::text($url, 1000);
        if ($u === '') {
            return '';
        }
        if ($allowAnchor && str_starts_with($u, '#')) {
            return $u;
        }
        if (str_starts_with($u, '/')) {
            return $u;
        }
        if (preg_match('/^(https?:\/\/|mailto:|tel:)/i', $u) === 1) {
            return $u;
        }

        return '';
    }
}
