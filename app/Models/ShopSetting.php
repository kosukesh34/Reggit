<?php

namespace App\Models;

use App\Enums\MemberRegistrationMode;
use App\Enums\ProfileCustomFieldInputType;
use App\Models\Settings\ProjectAdminUiSetting;
use App\Models\Settings\ProjectAnnouncementSetting;
use App\Models\Settings\ProjectBusinessSetting;
use App\Models\Settings\ProjectFeatureFlags;
use App\Models\Settings\ProjectLineLiffSetting;
use App\Models\Settings\ProjectMemberPointsSetting;
use App\Models\Settings\ProjectMemberRankSetting;
use App\Models\Settings\ProjectProfileSmaregiSetting;
use App\Models\Settings\ProjectWalletSetting;
use App\Support\LiffDesignerSanitizer;
use App\Support\ShopSettingLiffDefaults;
use App\Util\AdminContext;
use App\Util\StrHelper;

/**
 * 店舗設定のファサード。キーごとのテーブル（project_business_settings 等）を参照する。
 * 旧 shop_settings (EAV) は廃止し、設定種別ごとに正規化テーブルに分割済み。
 */
class ShopSetting
{
    private const DEFAULT_RESERVATION_CAPACITY = 20;

    /** キー → 設定モデルクラス（キーごとにテーブルが分かれている） */
    private const KEY_TO_MODEL = [
        'business_hours_start' => ProjectBusinessSetting::class,
        'business_hours_end' => ProjectBusinessSetting::class,
        'reservation_time_slots' => ProjectBusinessSetting::class,
        'closed_days' => ProjectBusinessSetting::class,
        'closed_dates' => ProjectBusinessSetting::class,
        'advance_booking_days' => ProjectBusinessSetting::class,
        'reservation_capacity_per_slot' => ProjectBusinessSetting::class,
        'queue_avg_serve_minutes' => ProjectBusinessSetting::class,
        'queue_require_membership' => ProjectBusinessSetting::class,
        'queue_channel_line' => ProjectBusinessSetting::class,
        'queue_channel_native' => ProjectBusinessSetting::class,
        'line_primary_color' => ProjectLineLiffSetting::class,
        'line_primary_dark' => ProjectLineLiffSetting::class,
        'line_success_color' => ProjectLineLiffSetting::class,
        'line_danger_color' => ProjectLineLiffSetting::class,
        'line_liff_id' => ProjectLineLiffSetting::class,
        'line_liff_url' => ProjectLineLiffSetting::class,
        'line_channel_access_token' => ProjectLineLiffSetting::class,
        'line_channel_secret' => ProjectLineLiffSetting::class,
        'line_friend_add_url' => ProjectLineLiffSetting::class,
        'liff_builder_blocks' => ProjectLineLiffSetting::class,
        'liff_tab_layouts' => ProjectLineLiffSetting::class,
        'liff_detail_layouts' => ProjectLineLiffSetting::class,
        'liff_tab_settings' => ProjectLineLiffSetting::class,
        'liff_saved_screens' => ProjectLineLiffSetting::class,
        'liff_custom_css' => ProjectLineLiffSetting::class,
        'order_mode' => ProjectLineLiffSetting::class,
        'order_line_notify_enabled' => ProjectLineLiffSetting::class,
        'order_ready_line_notify_enabled' => ProjectLineLiffSetting::class,
        'kitchen_access_token' => ProjectLineLiffSetting::class,
        'frontend_menu_items' => ProjectLineLiffSetting::class,
        'feature_home_enabled' => ProjectFeatureFlags::class,
        'feature_orders_enabled' => ProjectFeatureFlags::class,
        'feature_reservations_enabled' => ProjectFeatureFlags::class,
        'feature_queue_enabled' => ProjectFeatureFlags::class,
        'feature_stamps_enabled' => ProjectFeatureFlags::class,
        'feature_coupons_enabled' => ProjectFeatureFlags::class,
        'feature_members_enabled' => ProjectFeatureFlags::class,
        'feature_announcements_enabled' => ProjectFeatureFlags::class,
        'feature_payments_enabled' => ProjectFeatureFlags::class,
        'feature_gallery_enabled' => ProjectFeatureFlags::class,
        'feature_gacha_enabled' => ProjectFeatureFlags::class,
        'feature_scratch_enabled' => ProjectFeatureFlags::class,
        'admin_sidebar_color' => ProjectAdminUiSetting::class,
        'admin_header_color' => ProjectAdminUiSetting::class,
        'admin_primary_color' => ProjectAdminUiSetting::class,
        'stamp_default_image_url' => ProjectAdminUiSetting::class,
        'coupon_default_image_url' => ProjectAdminUiSetting::class,
        'frontend_logo_url' => ProjectAdminUiSetting::class,
        'frontend_header_text' => ProjectAdminUiSetting::class,
        'profile_field_settings' => ProjectProfileSmaregiSetting::class,
        'member_registration_mode' => ProjectProfileSmaregiSetting::class,
        'smaregi_contract_id' => ProjectProfileSmaregiSetting::class,
        'smaregi_client_id' => ProjectProfileSmaregiSetting::class,
        'smaregi_client_secret' => ProjectProfileSmaregiSetting::class,
        'smaregi_store_id' => ProjectProfileSmaregiSetting::class,
        'smaregi_terminal_id' => ProjectProfileSmaregiSetting::class,
        'point_earn_rate' => ProjectMemberPointsSetting::class,
        'points_per_order' => ProjectMemberPointsSetting::class,
        'stamp_earn_per_amount' => ProjectMemberPointsSetting::class,
        'order_stamp_card_id' => ProjectMemberPointsSetting::class,
        'points_valid_days' => ProjectMemberPointsSetting::class,
        'member_rank_basis' => ProjectMemberRankSetting::class,
        'member_code_type' => ProjectMemberRankSetting::class,
        'member_code_format' => ProjectMemberRankSetting::class,
        'member_code_prefix' => ProjectMemberRankSetting::class,
        'member_code_length' => ProjectMemberRankSetting::class,
        'member_code_next_number' => ProjectMemberRankSetting::class,
        'show_points_display' => ProjectMemberRankSetting::class,
        'show_rank_progress' => ProjectMemberRankSetting::class,
        'show_member_card_for_guest' => ProjectMemberRankSetting::class,
        'member_card_design' => ProjectMemberRankSetting::class,
        'member_card_display_type' => ProjectMemberRankSetting::class,
        'member_card_code_type' => ProjectMemberRankSetting::class,
        'member_rank_settings' => ProjectMemberRankSetting::class,
        'wallet_google_program_logo_url' => ProjectWalletSetting::class,
        'wallet_google_hero_image_url' => ProjectWalletSetting::class,
        'announcement_default_pinned' => ProjectAnnouncementSetting::class,
        'announcement_default_expiry_days' => ProjectAnnouncementSetting::class,
    ];

    public static function getValue(string $key, $default = null, ?int $projectId = null)
    {
        $projectId = $projectId ?? AdminContext::getCurrentProjectId() ?? 1;
        $modelClass = self::KEY_TO_MODEL[$key] ?? null;
        if ($modelClass === null) {
            return $default;
        }

        $row = $modelClass::firstOrCreate(
            ['project_id' => $projectId],
            $modelClass::getDefaultAttributes()
        );

        $value = $row->getAttribute($key);
        if ($value === null) {
            return $default;
        }

        return $value;
    }

    public static function setValue(string $key, $value, string $type = 'string', ?int $projectId = 1): void
    {
        $projectId = $projectId ?? AdminContext::getCurrentProjectId() ?? 1;
        $modelClass = self::KEY_TO_MODEL[$key] ?? null;
        if ($modelClass === null) {
            return;
        }

        $row = $modelClass::firstOrCreate(
            ['project_id' => $projectId],
            $modelClass::getDefaultAttributes()
        );

        if ($type === 'json' && is_string($value)) {
            $value = json_decode($value, true) ?? $value;
        }
        $row->setAttribute($key, $value);
        $row->save();
    }

    public static function getBusinessHours(?int $projectId = 1): array
    {
        return [
            'start' => self::getValue('business_hours_start', '10:00', $projectId),
            'end' => self::getValue('business_hours_end', '22:00', $projectId),
        ];
    }

    public static function getReservationTimeSlots(?int $projectId = 1): array
    {
        return self::getValue('reservation_time_slots', ['10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00'], $projectId);
    }

    public static function getClosedDays(?int $projectId = 1): array
    {
        return self::getValue('closed_days', [], $projectId);
    }

    public static function getClosedDates(?int $projectId = 1): array
    {
        return self::getValue('closed_dates', [], $projectId);
    }

    public static function getAdvanceBookingDays(?int $projectId = 1): int
    {
        return self::getValue('advance_booking_days', 30, $projectId);
    }

    public static function getReservationCapacityPerSlot(?int $projectId = 1): int
    {
        return self::getValue('reservation_capacity_per_slot', self::DEFAULT_RESERVATION_CAPACITY, $projectId);
    }

    /** 順番待ち 1組あたりの平均対応時間（分）。予測待ち時間の計算に使用。 */
    public static function getQueueAvgServeMinutes(?int $projectId = 1): int
    {
        return self::getValue('queue_avg_serve_minutes', 10, $projectId);
    }

    public static function getQueueRequireMembership(?int $projectId = 1): bool
    {
        return (bool) self::getValue('queue_require_membership', false, $projectId);
    }

    public static function getQueueChannelLine(?int $projectId = 1): bool
    {
        return (bool) self::getValue('queue_channel_line', true, $projectId);
    }

    public static function getQueueChannelNative(?int $projectId = 1): bool
    {
        return (bool) self::getValue('queue_channel_native', false, $projectId);
    }

    public static function getLineThemeColors(?int $projectId = 1): array
    {
        $primary = self::getValue('line_primary_color', '#0E7C66', $projectId);

        return [
            'primary' => $primary,
            'primary_rgb' => self::hexToRgb($primary),
            'primary_dark' => self::getValue('line_primary_dark', '#0A6B58', $projectId),
            'success' => self::getValue('line_success_color', '#059669', $projectId),
            'danger' => self::getValue('line_danger_color', '#dc2626', $projectId),
        ];
    }

    private static function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return '0, 0, 0';
        }
        $r = (int) hexdec(substr($hex, 0, 2));
        $g = (int) hexdec(substr($hex, 2, 2));
        $b = (int) hexdec(substr($hex, 4, 2));

        return "{$r}, {$g}, {$b}";
    }

    /** フロント（ユーザー向け）のヘッダー・フッターに表示するメニュー項目のデフォルト */
    public static function defaultFrontendMenuItems(): array
    {
        return [
            ['tab' => 'home', 'label' => 'ホーム', 'icon' => 'fa-house', 'order' => 0],
            ['tab' => 'products', 'label' => '商品', 'icon' => 'fa-utensils', 'order' => 1],
            ['tab' => 'orders', 'label' => '注文', 'icon' => 'fa-receipt', 'order' => 2],
            ['tab' => 'stamps', 'label' => 'スタンプ', 'icon' => 'fa-stamp', 'order' => 3],
            ['tab' => 'coupons', 'label' => 'クーポン', 'icon' => 'fa-ticket-alt', 'order' => 4],
            ['tab' => 'queue', 'label' => '順番待ち', 'icon' => 'fa-list-ol', 'order' => 5],
            ['tab' => 'reservations', 'label' => '予約', 'icon' => 'fa-calendar-check', 'order' => 6],
            ['tab' => 'member', 'label' => '会員証', 'icon' => 'fa-id-card', 'order' => 7],
            ['tab' => 'gallery', 'label' => 'ギャラリー', 'icon' => 'fa-images', 'order' => 8],
            ['tab' => 'announcements', 'label' => 'お知らせ', 'icon' => 'fa-bullhorn', 'order' => 9],
        ];
    }

    /** フロントメニュー項目を取得（ホーム設定で「表示する」にしたもののみ・order でソート） */
    public static function getFrontendMenuItems(?int $projectId = null): array
    {
        $projectId = $projectId ?? 1;
        $items = self::getValue('frontend_menu_items', null, $projectId);
        if ($items === null || ! is_array($items)) {
            return self::defaultFrontendMenuItems();
        }
        $defaults = collect(self::defaultFrontendMenuItems())->keyBy('tab');
        $merged = [];
        foreach ($items as $item) {
            $tab = $item['tab'] ?? null;
            $isLinkTab = in_array($tab, LiffDesignerSanitizer::LINK_MENU_TABS, true);
            if (StrHelper::isEmptyStringOrNull($tab) || ($defaults->has($tab) === false && $isLinkTab === false)) {
                continue;
            }
            if ($isLinkTab) {
                if (isset($item['enabled']) && $item['enabled'] === false) {
                    continue;
                }
                if (! isset($item['url']) || preg_match('/^https:\/\//i', (string) $item['url']) !== 1) {
                    continue;
                }
                // リンクタブはアプリ内埋め込みを優先（旧デフォルト external も上書き）
                $item['open'] = 'embed';
                $merged[] = $item;

                continue;
            }
            $row = array_merge($defaults->get($tab), $item);
            if (isset($row['enabled']) && $row['enabled'] === false) {
                continue;
            }
            $row['icon'] = self::normalizeFrontendMenuIcon((string) $tab, (string) ($row['icon'] ?? ''));
            $row['label'] = (string) ($row['label'] ?? $defaults->get($tab)['label']);
            $merged[] = $row;
        }
        if ($merged === []) {
            return self::defaultFrontendMenuItems();
        }
        usort($merged, fn ($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));

        return $merged;
    }

    /** 旧アイコン名を現行デフォルトへ寄せ、デザイナー／本番の見た目差を防ぐ */
    public static function normalizeFrontendMenuIcon(string $tab, string $icon): string
    {
        $defaults = collect(self::defaultFrontendMenuItems())->keyBy('tab');
        $fallback = (string) ($defaults->get($tab)['icon'] ?? 'fa-circle');
        if ($icon === '') {
            return $fallback;
        }

        $aliases = [
            'fa-home' => 'fa-house',
            'fa-calendar-alt' => 'fa-calendar-check',
            'fa-users' => 'fa-list-ol',
        ];
        if ($tab === 'member' && ($icon === 'fa-user' || $icon === 'fa-users')) {
            return 'fa-id-card';
        }
        if ($tab === 'queue' && $icon === 'fa-users') {
            return 'fa-list-ol';
        }

        return $aliases[$icon] ?? $icon;
    }

    /** フロントメニュー項目を保存 */
    public static function setFrontendMenuItems(array $items, ?int $projectId = null): void
    {
        $projectId = $projectId ?? 1;
        self::setValue('frontend_menu_items', $items, 'json', $projectId);
    }

    // ── 機能フラグ ──

    /** 機能フラグのデフォルト値 */
    public const FEATURE_FLAGS = [
        'feature_home_enabled' => true,
        'feature_orders_enabled' => true,
        'feature_reservations_enabled' => true,
        'feature_queue_enabled' => true,
        'feature_stamps_enabled' => true,
        'feature_coupons_enabled' => true,
        'feature_members_enabled' => true,
        'feature_announcements_enabled' => true,
        'feature_payments_enabled' => true,
        'feature_gallery_enabled' => true,
        'feature_gacha_enabled' => false,
        'feature_scratch_enabled' => false,
    ];

    /** 機能フラグの表示名（静的デフォルト。動的ラベルは featureFlagLabels() を使う） */
    public const FEATURE_FLAG_LABELS = [
        'feature_home_enabled' => 'ホーム',
        'feature_orders_enabled' => '注文（商品・履歴）',
        'feature_reservations_enabled' => '予約',
        'feature_queue_enabled' => '順番待ち',
        'feature_stamps_enabled' => 'スタンプカード',
        'feature_coupons_enabled' => 'クーポン',
        'feature_members_enabled' => '会員管理',
        'feature_announcements_enabled' => 'お知らせ',
        'feature_gallery_enabled' => 'ギャラリー',
    ];

    /** ガチャ設定画面のみで編集し、機能設定のチェック保存では維持するフラグ */
    public const LOTTERY_MANAGED_FEATURE_FLAGS = [
        'feature_gacha_enabled',
        'feature_scratch_enabled',
    ];

    /** config/native_app.php を反映した機能フラグ表示名 */
    public static function featureFlagLabels(): array
    {
        return self::FEATURE_FLAG_LABELS;
    }

    public const ORDER_MODE_BOTH = 'both';

    public const ORDER_MODE_MOBILE_ONLY = 'mobile_only';

    public const ORDER_MODE_IN_STORE_ONLY = 'in_store_only';

    // ── LIFF ビルダー ──

    public static function getLiffBuilderBlocks(?int $projectId = null): array
    {
        $blocks = self::getValue('liff_builder_blocks', null, $projectId);
        if (! is_array($blocks) || $blocks === []) {
            return ShopSettingLiffDefaults::defaultLiffBuilderTemplates()[0]['blocks'];
        }

        return $blocks;
    }

    public static function getOrderMode(?int $projectId = null): string
    {
        $value = (string) self::getValue('order_mode', self::ORDER_MODE_BOTH, $projectId);
        if (in_array($value, [self::ORDER_MODE_BOTH, self::ORDER_MODE_MOBILE_ONLY, self::ORDER_MODE_IN_STORE_ONLY], true)) {
            return $value;
        }

        return self::ORDER_MODE_BOTH;
    }

    public static function setOrderMode(string $mode, ?int $projectId = null): void
    {
        if (! in_array($mode, [self::ORDER_MODE_BOTH, self::ORDER_MODE_MOBILE_ONLY, self::ORDER_MODE_IN_STORE_ONLY], true)) {
            $mode = self::ORDER_MODE_BOTH;
        }
        self::setValue('order_mode', $mode, 'string', $projectId);
    }

    public static function isOrderLineNotifyEnabled(?int $projectId = null): bool
    {
        return (bool) self::getValue('order_line_notify_enabled', true, $projectId);
    }

    public static function setOrderLineNotifyEnabled(bool $enabled, ?int $projectId = null): void
    {
        self::setValue('order_line_notify_enabled', $enabled, 'boolean', $projectId);
    }

    public static function isOrderReadyLineNotifyEnabled(?int $projectId = null): bool
    {
        return (bool) self::getValue('order_ready_line_notify_enabled', true, $projectId);
    }

    public static function setOrderReadyLineNotifyEnabled(bool $enabled, ?int $projectId = null): void
    {
        self::setValue('order_ready_line_notify_enabled', $enabled, 'boolean', $projectId);
    }

    public static function getKitchenAccessToken(?int $projectId = null): string
    {
        return trim((string) self::getValue('kitchen_access_token', '', $projectId));
    }

    public static function setKitchenAccessToken(?string $token, ?int $projectId = null): void
    {
        self::setValue('kitchen_access_token', $token !== null ? trim($token) : '', 'string', $projectId);
    }

    public static function setLiffBuilderBlocks(array $blocks, ?int $projectId = null): void
    {
        self::setValue('liff_builder_blocks', $blocks, 'json', $projectId);
    }

    public static function defaultLiffBuilderTemplates(): array
    {
        return ShopSettingLiffDefaults::defaultLiffBuilderTemplates();
    }

    public static function defaultLiffDesignerTemplates(): array
    {
        return ShopSettingLiffDefaults::defaultLiffDesignerTemplates();
    }

    // ── LIFF タブレイアウト ──

    public static function getLiffTabLayouts(?int $projectId = null): array
    {
        $layouts = self::getValue('liff_tab_layouts', null, $projectId);
        if (! is_array($layouts) || $layouts === []) {
            return ShopSettingLiffDefaults::defaultLiffTabLayouts();
        }

        return ShopSettingLiffDefaults::ensureLiffTabLayouts($layouts);
    }

    public static function getLiffCustomCss(?int $projectId = null): string
    {
        return (string) self::getValue('liff_custom_css', '', $projectId);
    }

    public static function setLiffCustomCss(string $css, ?int $projectId = null): void
    {
        self::setValue('liff_custom_css', $css, 'string', $projectId);
    }

    public static function getLiffTabSettings(?int $projectId = null): array
    {
        $settings = self::getValue('liff_tab_settings', null, $projectId);

        return is_array($settings) && $settings !== [] ? $settings : ShopSettingLiffDefaults::defaultLiffTabSettings();
    }

    public static function setLiffTabSettings(array $settings, ?int $projectId = null): void
    {
        self::setValue('liff_tab_settings', $settings, 'json', $projectId);
    }

    public static function setLiffTabLayouts(array $layouts, ?int $projectId = null): void
    {
        self::setValue('liff_tab_layouts', ShopSettingLiffDefaults::ensureLiffTabLayouts($layouts), 'json', $projectId);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function getLiffDetailLayouts(?int $projectId = null): array
    {
        $layouts = self::getValue('liff_detail_layouts', null, $projectId);
        if (! is_array($layouts) || $layouts === []) {
            return ShopSettingLiffDefaults::defaultLiffDetailLayouts();
        }

        return ShopSettingLiffDefaults::ensureLiffDetailLayouts($layouts);
    }

    /**
     * @param  array<string, mixed>  $layouts
     */
    public static function setLiffDetailLayouts(array $layouts, ?int $projectId = null): void
    {
        self::setValue('liff_detail_layouts', ShopSettingLiffDefaults::ensureLiffDetailLayouts($layouts), 'json', $projectId);
    }

    /** LIFF 保存画面の最大件数 */
    public const LIFF_SAVED_SCREENS_MAX = 10;

    public static function getLiffSavedScreens(?int $projectId = null): array
    {
        $screens = self::getValue('liff_saved_screens', [], $projectId);

        return is_array($screens) ? $screens : [];
    }

    public static function setLiffSavedScreens(array $screens, ?int $projectId = null): void
    {
        self::setValue('liff_saved_screens', $screens, 'json', $projectId);
    }

    public static function getLineLiffId(?int $projectId = null): string
    {
        $value = trim((string) self::getValue('line_liff_id', '', $projectId));
        if ($value !== '') {
            return $value;
        }

        return trim((string) config('line.liff_id', ''));
    }

    public static function setLineLiffId(?string $liffId, ?int $projectId = null): void
    {
        self::setValue('line_liff_id', trim((string) ($liffId ?? '')), 'string', $projectId);
    }

    public static function getLineLiffUrl(?int $projectId = null): string
    {
        $value = trim((string) self::getValue('line_liff_url', '', $projectId));
        if ($value !== '') {
            return $value;
        }

        return trim((string) config('line.liff_url', ''));
    }

    public static function setLineLiffUrl(?string $liffUrl, ?int $projectId = null): void
    {
        self::setValue('line_liff_url', trim((string) ($liffUrl ?? '')), 'string', $projectId);
    }

    public static function getLineChannelAccessToken(?int $projectId = null): string
    {
        $value = trim((string) self::getValue('line_channel_access_token', '', $projectId));
        if ($value !== '') {
            return $value;
        }

        return trim((string) (config('line.channel_access_token') ?: env('LINE_CHANNEL_ACCESS_TOKEN', '')));
    }

    public static function setLineChannelAccessToken(?string $token, ?int $projectId = null): void
    {
        $trimmed = trim((string) ($token ?? ''));
        if ($trimmed === '') {
            return;
        }
        self::setValue('line_channel_access_token', $trimmed, 'string', $projectId);
    }

    public static function getLineChannelSecret(?int $projectId = null): string
    {
        $value = trim((string) self::getValue('line_channel_secret', '', $projectId));
        if ($value !== '') {
            return $value;
        }

        return trim((string) (config('line.channel_secret') ?: env('LINE_CHANNEL_SECRET', '')));
    }

    public static function setLineChannelSecret(?string $secret, ?int $projectId = null): void
    {
        $trimmed = trim((string) ($secret ?? ''));
        if ($trimmed === '') {
            return;
        }
        self::setValue('line_channel_secret', $trimmed, 'string', $projectId);
    }

    public static function hasLineChannelAccessToken(?int $projectId = null): bool
    {
        return self::getLineChannelAccessToken($projectId) !== '';
    }

    /** LINE公式アカウントの友だち追加URL（メッセージ・クーポン配布用）。例: https://line.me/R/ti/p/@xxx */
    public static function getLineFriendAddUrl(?int $projectId = null): string
    {
        return trim((string) self::getValue('line_friend_add_url', '', $projectId));
    }

    public static function setLineFriendAddUrl(?string $url, ?int $projectId = null): void
    {
        self::setValue('line_friend_add_url', trim((string) ($url ?? '')), 'string', $projectId);
    }

    public static function defaultLiffTabLayouts(): array
    {
        return ShopSettingLiffDefaults::defaultLiffTabLayouts();
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function defaultLiffDetailLayouts(): array
    {
        return ShopSettingLiffDefaults::defaultLiffDetailLayouts();
    }

    /**
     * 保存済みレイアウトをタブ単位で正規化する。
     *
     * ルール:
     * - タブが空ならデフォルトレイアウトを採用
     * - 既存ブロックは順序を変えずに維持する
     *
     * @param  array<string, mixed>  $layouts
     * @return array<string, array<int, array<string, mixed>>>
     */
    public static function ensureLiffTabLayouts(array $layouts): array
    {
        return ShopSettingLiffDefaults::ensureLiffTabLayouts($layouts);
    }

    public static function defaultLiffTabSettings(): array
    {
        return ShopSettingLiffDefaults::defaultLiffTabSettings();
    }

    /** 全機能フラグを取得 */
    public static function getFeatureFlags(?int $projectId = null): array
    {
        $flags = [];
        foreach (self::FEATURE_FLAGS as $key => $default) {
            $flags[$key] = (bool) self::getValue($key, $default, $projectId);
        }

        return $flags;
    }

    /** 指定機能が有効か */
    public static function isFeatureEnabled(string $featureKey, ?int $projectId = null): bool
    {
        $default = self::FEATURE_FLAGS[$featureKey] ?? true;

        return (bool) self::getValue($featureKey, $default, $projectId);
    }

    /**
     * チェックボックス形式の保存用に、ガチャ系フラグを現行値で補完する。
     * 有効なときだけキーを付け、無効は未指定のままにする（missing=OFF と両立）。
     *
     * @param  array<string, bool|string|int|null>  $flags
     * @return array<string, bool|string|int|null>
     */
    public static function withPreservedLotteryManagedFlags(array $flags, ?int $projectId = null): array
    {
        $current = self::getFeatureFlags($projectId);
        foreach (self::LOTTERY_MANAGED_FEATURE_FLAGS as $key) {
            if (array_key_exists($key, $flags)) {
                continue;
            }
            if (($current[$key] ?? false) === true) {
                $flags[$key] = true;
            }
        }

        return $flags;
    }

    /** 機能フラグを一括更新（未指定キーは OFF。false も OFF として扱う） */
    public static function setFeatureFlags(array $flags, ?int $projectId = null): void
    {
        foreach (self::FEATURE_FLAGS as $key => $default) {
            $enabled = array_key_exists($key, $flags) && self::isTruthyFeatureFlag($flags[$key]);
            self::setValue($key, $enabled ? '1' : '0', 'boolean', $projectId);
        }
    }

    /**
     * @param  bool|int|float|string|null  $value
     */
    private static function isTruthyFeatureFlag($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes'], true);
        }

        return false;
    }

    // ── プロフィール項目設定（スマレジ連携・必須/任意・任意項目） ──

    /** プロフィール項目のキーとラベル（フォーム表示用。一部はローカル専用） */
    public const PROFILE_SMAREGI_FIELDS = [
        'name' => 'お名前（姓・名）',
        'kana' => 'フリガナ（姓・名）',
        'phone' => '電話番号',
        'email' => 'メールアドレス',
        'postal_code' => '郵便番号',
        'prefecture' => '都道府県',
        'city' => '市区町村',
        'street' => '番地・建物名',
        'address' => '住所（自由入力）',
        'birthday' => '生年月日',
        'sex' => '性別',
        'mail_receive_flag' => '店舗からの案内メールを受け取る',
    ];

    /**
     * スマレジ会員 API に連携する項目キー。
     * prefecture / city / street は住所補助用で、スマレジへは address にまとめて送る。
     */
    public const PROFILE_SMAREGI_LINKED_KEYS = [
        'name',
        'kana',
        'phone',
        'email',
        'postal_code',
        'address',
        'birthday',
        'sex',
        'mail_receive_flag',
    ];

    /** 追加項目の上限 */
    public const PROFILE_CUSTOM_FIELD_MAX = 5;

    /** プロフィール項目設定のデフォルト（スマレジ項目は表示ON・名前と電話は必須、任意項目は空） */
    public static function defaultProfileFieldSettings(): array
    {
        $smaregi = [];
        foreach (self::PROFILE_SMAREGI_FIELDS as $key => $label) {
            $smaregi[$key] = [
                'label' => $label,
                // 案内メールは既存会員の未設定上書きを避けるため、管理者が明示ONするまで非表示
                'enabled' => $key !== 'mail_receive_flag',
                'required' => in_array($key, ['name', 'phone'], true),
            ];
        }

        return [
            'smaregi_fields' => $smaregi,
            'custom_fields' => [],
        ];
    }

    public static function isSmaregiLinkedProfileField(string $key): bool
    {
        return in_array($key, self::PROFILE_SMAREGI_LINKED_KEYS, true);
    }

    /** プロフィール項目設定を取得（会員登録・編集フォームの表示とバリデーションに使用） */
    public static function getProfileFieldSettings(?int $projectId = null): array
    {
        $projectId = $projectId ?? 1;
        $raw = self::getValue('profile_field_settings', null, $projectId);
        if (! is_array($raw)) {
            return self::defaultProfileFieldSettings();
        }
        $defaults = self::defaultProfileFieldSettings();
        $smaregi = $defaults['smaregi_fields'];
        foreach ($defaults['smaregi_fields'] as $key => $def) {
            if (isset($raw['smaregi_fields'][$key]) && is_array($raw['smaregi_fields'][$key])) {
                $smaregi[$key] = [
                    'label' => $raw['smaregi_fields'][$key]['label'] ?? $def['label'],
                    'enabled' => (bool) ($raw['smaregi_fields'][$key]['enabled'] ?? $def['enabled']),
                    'required' => (bool) ($raw['smaregi_fields'][$key]['required'] ?? $def['required']),
                ];
            }
            // スマレジ API 必須の氏名は非表示・任意にできない
            if ($key === 'name') {
                $smaregi[$key]['enabled'] = true;
                $smaregi[$key]['required'] = true;
            }
        }
        $custom = isset($raw['custom_fields']) && is_array($raw['custom_fields'])
            ? array_slice(array_values($raw['custom_fields']), 0, self::PROFILE_CUSTOM_FIELD_MAX)
            : [];
        $custom = array_map(static function (array $cf): array {
            $inputType = (string) ($cf['input_type'] ?? ProfileCustomFieldInputType::TEXT);
            if (! array_key_exists($inputType, ProfileCustomFieldInputType::getValues())) {
                $inputType = ProfileCustomFieldInputType::TEXT;
            }
            $values = [];
            if (isset($cf['values']) && is_array($cf['values'])) {
                foreach ($cf['values'] as $value) {
                    $value = trim((string) $value);
                    if ($value !== '') {
                        $values[] = $value;
                    }
                }
                $values = array_slice($values, 0, ProfileCustomFieldInputType::VALUES_MAX);
            }

            return [
                'key' => (string) ($cf['key'] ?? ''),
                'label' => (string) ($cf['label'] ?? ''),
                'required' => (bool) ($cf['required'] ?? false),
                'input_type' => $inputType,
                'values' => $values,
                'description' => isset($cf['description']) ? (string) $cf['description'] : '',
            ];
        }, array_filter($custom, static fn ($row): bool => is_array($row)));

        return [
            'smaregi_fields' => $smaregi,
            'custom_fields' => array_values($custom),
        ];
    }

    /** プロフィール項目設定を保存 */
    public static function setProfileFieldSettings(array $settings, ?int $projectId = null): void
    {
        $projectId = $projectId ?? 1;
        self::setValue('profile_field_settings', $settings, 'json', $projectId);
    }

    public static function getMemberRegistrationMode(?int $projectId = null): string
    {
        $value = (string) self::getValue('member_registration_mode', MemberRegistrationMode::OPTIONAL, $projectId);
        if (array_key_exists($value, MemberRegistrationMode::getValues())) {
            return $value;
        }

        return MemberRegistrationMode::OPTIONAL;
    }

    public static function setMemberRegistrationMode(string $mode, ?int $projectId = null): void
    {
        if (! array_key_exists($mode, MemberRegistrationMode::getValues())) {
            $mode = MemberRegistrationMode::OPTIONAL;
        }
        self::setValue('member_registration_mode', $mode, 'string', $projectId);
    }

    // ── UI カスタマイズ（管理画面） ──

    public static function getAdminSidebarColor(?int $projectId = null): ?string
    {
        return self::getValue('admin_sidebar_color', null, $projectId);
    }

    public static function getAdminHeaderColor(?int $projectId = null): ?string
    {
        return self::getValue('admin_header_color', null, $projectId);
    }

    public static function getStampDefaultImageUrl(?int $projectId = null): ?string
    {
        return self::getValue('stamp_default_image_url', null, $projectId);
    }

    public static function getCouponDefaultImageUrl(?int $projectId = null): ?string
    {
        return self::getValue('coupon_default_image_url', null, $projectId);
    }

    public static function getPointEarnRate(?int $projectId = null): int
    {
        return (int) self::getValue('point_earn_rate', 100, $projectId);
    }

    /** 注文ごとの固定ポイント（0で無効） */
    public static function getPointsPerOrder(?int $projectId = null): int
    {
        return (int) self::getValue('points_per_order', 0, $projectId);
    }

    /** スタンプ1個付与につき何円か（0で無効）。例: 1000 = 1000円で1スタンプ */
    public static function getStampEarnPerAmount(?int $projectId = null): int
    {
        return (int) self::getValue('stamp_earn_per_amount', 0, $projectId);
    }

    /** 注文時にスタンプを付与するスタンプカードID（nullで付与しない） */
    public static function getOrderStampCardId(?int $projectId = null): ?int
    {
        $v = self::getValue('order_stamp_card_id', null, $projectId);
        if ($v === null || $v === '') {
            return null;
        }
        $id = (int) $v;

        return $id > 0 ? $id : null;
    }

    public static function getPointsValidDays(?int $projectId = null): int
    {
        return (int) self::getValue('points_valid_days', 365, $projectId);
    }

    public static function getRankBasis(?int $projectId = null): string
    {
        return (string) self::getValue('member_rank_basis', 'points', $projectId);
    }

    /** 会員コード生成: sequential=連番, random=ランダム */
    public static function getMemberCodeType(?int $projectId = null): string
    {
        return (string) self::getValue('member_code_type', 'random', $projectId);
    }

    /** 会員コード形式: numeric=数字のみ, alphanumeric=英数字混在 */
    public static function getMemberCodeFormat(?int $projectId = null): string
    {
        return (string) self::getValue('member_code_format', 'numeric', $projectId);
    }

    /** 会員コード桁数（プレフィックス除く） */
    public static function getMemberCodeLength(?int $projectId = null): int
    {
        return (int) self::getValue('member_code_length', 10, $projectId);
    }

    /** 会員コード先頭プレフィックス（任意） */
    public static function getMemberCodePrefix(?int $projectId = null): string
    {
        return (string) self::getValue('member_code_prefix', '', $projectId);
    }

    /** 連番時の次に使う番号（内部用） */
    public static function getMemberCodeNextNumber(?int $projectId = null): int
    {
        return (int) self::getValue('member_code_next_number', 1, $projectId);
    }

    public static function setMemberCodeNextNumber(int $next, ?int $projectId = null): void
    {
        self::setValue('member_code_next_number', $next, 'integer', $projectId);
    }

    /** フロントでポイント数を表示するか */
    public static function getShowPointsDisplay(?int $projectId = null): bool
    {
        return (bool) self::getValue('show_points_display', true, $projectId);
    }

    /** フロントで「次のランクまで」を表示するか */
    public static function getShowRankProgress(?int $projectId = null): bool
    {
        return (bool) self::getValue('show_rank_progress', true, $projectId);
    }

    /** 会員未登録（ゲスト）のときに会員証カードを表示するか。false のときはカードを表示せずメッセージのみ */
    public static function getShowMemberCardForGuest(?int $projectId = null): bool
    {
        return (bool) self::getValue('show_member_card_for_guest', true, $projectId);
    }

    public static function setShowMemberCardForGuest(bool $value, ?int $projectId = null): void
    {
        self::setValue('show_member_card_for_guest', $value, 'boolean', $projectId ?? 1);
    }

    /** 会員証のレイアウトデザイン。default, logo_top, logo_center, logo_header, compact, split_banner, minimal_line, emblem */
    public static function getMemberCardDesign(?int $projectId = null): string
    {
        $v = self::getValue('member_card_design', 'default', $projectId);

        return in_array($v, ['default', 'logo_top', 'logo_center', 'logo_header', 'compact', 'split_banner', 'minimal_line', 'emblem'], true) ? $v : 'default';
    }

    public static function setMemberCardDesign(string $value, ?int $projectId = null): void
    {
        $value = in_array($value, ['default', 'logo_top', 'logo_center', 'logo_header', 'compact', 'split_banner', 'minimal_line', 'emblem'], true) ? $value : 'default';
        self::setValue('member_card_design', $value, 'string', $projectId ?? 1);
    }

    /** 会員証の表示形式。card=カード型（裏表あり）, sheet=シート型（1面表示） */
    public static function getMemberCardDisplayType(?int $projectId = null): string
    {
        $v = self::getValue('member_card_display_type', 'card', $projectId);

        return in_array($v, ['card', 'sheet'], true) ? $v : 'card';
    }

    public static function setMemberCardDisplayType(string $value, ?int $projectId = null): void
    {
        $value = in_array($value, ['card', 'sheet'], true) ? $value : 'card';
        self::setValue('member_card_display_type', $value, 'string', $projectId ?? 1);
    }

    /** 会員証の会員番号表示。barcode=バーコード, qrcode=QRコード */
    public static function getMemberCardCodeType(?int $projectId = null): string
    {
        $v = self::getValue('member_card_code_type', 'barcode', $projectId);

        return in_array($v, ['barcode', 'qrcode'], true) ? $v : 'barcode';
    }

    public static function setMemberCardCodeType(string $value, ?int $projectId = null): void
    {
        $value = in_array($value, ['barcode', 'qrcode'], true) ? $value : 'barcode';
        self::setValue('member_card_code_type', $value, 'string', $projectId ?? 1);
    }

    public static function getMemberRankSettings(?int $projectId = null): array
    {
        $settings = self::getValue('member_rank_settings', null, $projectId);
        if (! is_array($settings) || $settings === []) {
            return self::defaultMemberRankSettings();
        }
        $normalized = [];
        foreach ($settings as $row) {
            if (! is_array($row)) {
                continue;
            }
            $key = $row['key'] ?? null;
            if (StrHelper::isEmptyStringOrNull($key)) {
                continue;
            }
            $normalized[] = [
                'key' => (string) $key,
                'label' => (string) ($row['label'] ?? $key),
                'min_points' => (int) ($row['min_points'] ?? 0),
                'is_active' => isset($row['is_active']) ? (bool) $row['is_active'] : true,
                'icon_url' => $row['icon_url'] ?? null,
                'stamp_background_url' => $row['stamp_background_url'] ?? null,
                'card_color' => isset($row['card_color']) && $row['card_color'] !== '' ? (string) $row['card_color'] : null,
                'order' => (int) ($row['order'] ?? 0),
                'point_earn_rate' => isset($row['point_earn_rate']) && $row['point_earn_rate'] !== null && $row['point_earn_rate'] !== ''
                    ? (int) $row['point_earn_rate']
                    : null,
                'smaregi_rank_id' => isset($row['smaregi_rank_id']) && trim((string) $row['smaregi_rank_id']) !== ''
                    ? (string) $row['smaregi_rank_id']
                    : null,
            ];
        }
        if ($normalized === []) {
            return self::defaultMemberRankSettings();
        }
        usort($normalized, fn ($a, $b) => ($a['min_points'] <=> $b['min_points']) ?: ($a['order'] <=> $b['order']));

        return $normalized;
    }

    /**
     * ランク別の付与レート（円/1pt）。未設定時はプロジェクト全体のデフォルトを返す。
     */
    public static function getPointEarnRateForRank(?int $projectId, ?string $rankKey): int
    {
        $default = self::getPointEarnRate($projectId);
        if ($rankKey === null || $rankKey === '') {
            return $default;
        }
        foreach (self::getMemberRankSettings($projectId) as $rank) {
            if ((string) ($rank['key'] ?? '') !== $rankKey) {
                continue;
            }
            $rate = $rank['point_earn_rate'] ?? null;
            if ($rate !== null && (int) $rate > 0) {
                return (int) $rate;
            }

            return $default;
        }

        return $default;
    }

    public static function setMemberRankSettings(array $settings, ?int $projectId = null): void
    {
        self::setValue('member_rank_settings', $settings, 'json', $projectId);
    }

    public static function defaultMemberRankSettings(): array
    {
        return [
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'is_active' => true, 'icon_url' => null, 'stamp_background_url' => null, 'card_color' => '#cd7f32', 'order' => 1, 'point_earn_rate' => null, 'smaregi_rank_id' => null],
            ['key' => 'silver', 'label' => 'シルバー', 'min_points' => 1000, 'is_active' => true, 'icon_url' => null, 'stamp_background_url' => null, 'card_color' => '#c0c0c0', 'order' => 2, 'point_earn_rate' => null, 'smaregi_rank_id' => null],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 5000, 'is_active' => true, 'icon_url' => null, 'stamp_background_url' => null, 'card_color' => '#ffd700', 'order' => 3, 'point_earn_rate' => null, 'smaregi_rank_id' => null],
            ['key' => 'platinum', 'label' => 'プラチナ', 'min_points' => 10000, 'is_active' => true, 'icon_url' => null, 'stamp_background_url' => null, 'card_color' => '#e5e4e2', 'order' => 4, 'point_earn_rate' => null, 'smaregi_rank_id' => null],
        ];
    }

    public static function getAdminPrimaryColor(?int $projectId = null): ?string
    {
        return self::getValue('admin_primary_color', null, $projectId);
    }

    public static function getFrontendLogoUrl(?int $projectId = null): ?string
    {
        return self::getValue('frontend_logo_url', null, $projectId);
    }

    /** Google Wallet プログラムロゴ画像URL（管理画面で設定、未設定時は config を使用） */
    public static function getWalletGoogleProgramLogoUrl(?int $projectId = null): ?string
    {
        $v = self::getValue('wallet_google_program_logo_url', null, $projectId);

        return is_string($v) && $v !== '' ? trim($v) : null;
    }

    /** Google Wallet ヒーロー画像URL（管理画面で設定、未設定時は config を使用） */
    public static function getWalletGoogleHeroImageUrl(?int $projectId = null): ?string
    {
        $v = self::getValue('wallet_google_hero_image_url', null, $projectId);

        return is_string($v) && $v !== '' ? trim($v) : null;
    }

    public static function setWalletGoogleProgramLogoUrl(?string $url, ?int $projectId = null): void
    {
        self::setValue('wallet_google_program_logo_url', trim((string) ($url ?? '')), 'string', $projectId ?? 1);
    }

    public static function setWalletGoogleHeroImageUrl(?string $url, ?int $projectId = null): void
    {
        self::setValue('wallet_google_hero_image_url', trim((string) ($url ?? '')), 'string', $projectId ?? 1);
    }

    public static function getFrontendHeaderText(?int $projectId = null): ?string
    {
        return self::getValue('frontend_header_text', null, $projectId);
    }

    public static function isDateAvailable(string $date, ?int $projectId = 1): bool
    {
        $dayOfWeek = (int) date('w', strtotime($date));
        $closedDays = self::getClosedDays($projectId);

        if (in_array($dayOfWeek, $closedDays, true)) {
            return false;
        }

        $dateStr = date('Y-m-d', strtotime($date));
        $closedDates = self::getClosedDates($projectId);

        if (in_array($dateStr, $closedDates, true)) {
            return false;
        }

        return true;
    }
}
