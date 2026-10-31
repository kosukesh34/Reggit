<?php

namespace App\Infrastructure\Persistence\Eloquent\Settings;

use App\Models\Settings\ProjectAdminUiSetting;
use App\Models\Settings\ProjectAnnouncementSetting;
use App\Models\Settings\ProjectBusinessSetting;
use App\Models\Settings\ProjectFeatureFlags;
use App\Models\Settings\ProjectLineLiffSetting;
use App\Models\Settings\ProjectMemberPointsSetting;
use App\Models\Settings\ProjectMemberRankSetting;
use App\Models\Settings\ProjectProfileSmaregiSetting;
use App\Models\Settings\ProjectWalletSetting;
use App\Util\AdminContext;
use App\Util\StoreContext;

/**
 * 店舗設定のキーと値の保存先。キーごとのテーブル（project_business_settings 等）と店舗ごとの上書きを解決する。
 * 分野ごとの読み書きは同じ名前空間の *SettingStore / FeatureFlagStore が担う。設定リポジトリアダプター経由で使用し、外部から直接参照しない。
 */
class ShopSettingStore
{
    public function __construct(
        private StoreContext $storeContext,
    ) {}

    /** キー → 設定モデルクラス（キーごとにテーブルが分かれている） */
    private const KEY_TO_MODEL = [
        'business_hours_start' => ProjectBusinessSetting::class,
        'business_hours_end' => ProjectBusinessSetting::class,
        'reservation_time_slots' => ProjectBusinessSetting::class,
        'closed_days' => ProjectBusinessSetting::class,
        'closed_dates' => ProjectBusinessSetting::class,
        'advance_booking_days' => ProjectBusinessSetting::class,
        'reservation_capacity_per_slot' => ProjectBusinessSetting::class,
        'reservation_capacity_by_people' => ProjectBusinessSetting::class,
        'reservation_max_party_size' => ProjectBusinessSetting::class,
        'reservation_same_day' => ProjectBusinessSetting::class,
        'reservation_lead_minutes' => ProjectBusinessSetting::class,
        'reservation_change_deadline_hours' => ProjectBusinessSetting::class,
        'reservation_reminder_hours' => ProjectBusinessSetting::class,
        'reservation_reminder_second_hours' => ProjectBusinessSetting::class,
        'reservation_reminder_message' => ProjectBusinessSetting::class,
        'queue_avg_serve_minutes' => ProjectBusinessSetting::class,
        'queue_require_membership' => ProjectBusinessSetting::class,
        'queue_channel_line' => ProjectBusinessSetting::class,
        'queue_channel_native' => ProjectBusinessSetting::class,
        'shop_shipping_fee' => ProjectBusinessSetting::class,
        'shop_shipping_fees_by_prefecture' => ProjectBusinessSetting::class,
        'shop_legal_notice' => ProjectBusinessSetting::class,
        'shop_free_shipping_threshold' => ProjectBusinessSetting::class,
        'shop_delivery_date_enabled' => ProjectBusinessSetting::class,
        'shop_delivery_lead_days' => ProjectBusinessSetting::class,
        'shop_delivery_range_days' => ProjectBusinessSetting::class,
        'shop_delivery_time_slots' => ProjectBusinessSetting::class,
        'shop_invoice_registration_number' => ProjectBusinessSetting::class,
        'line_primary_color' => ProjectLineLiffSetting::class,
        'line_primary_dark' => ProjectLineLiffSetting::class,
        'line_success_color' => ProjectLineLiffSetting::class,
        'line_danger_color' => ProjectLineLiffSetting::class,
        'line_liff_id' => ProjectLineLiffSetting::class,
        'line_liff_url' => ProjectLineLiffSetting::class,
        'line_channel_access_token' => ProjectLineLiffSetting::class,
        'line_channel_secret' => ProjectLineLiffSetting::class,
        'line_friend_add_url' => ProjectLineLiffSetting::class,
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
        'feature_shop_enabled' => ProjectFeatureFlags::class,
        'feature_tickets_enabled' => ProjectFeatureFlags::class,
        'feature_surveys_enabled' => ProjectFeatureFlags::class,
        'feature_popups_enabled' => ProjectFeatureFlags::class,
        'feature_chat_enabled' => ProjectFeatureFlags::class,
        'feature_chat_liff_enabled' => ProjectFeatureFlags::class,
        'feature_gacha_enabled' => ProjectFeatureFlags::class,
        'feature_scratch_enabled' => ProjectFeatureFlags::class,
        'feature_roulette_enabled' => ProjectFeatureFlags::class,
        'admin_sidebar_color' => ProjectAdminUiSetting::class,
        'admin_header_color' => ProjectAdminUiSetting::class,
        'admin_primary_color' => ProjectAdminUiSetting::class,
        'stamp_default_image_url' => ProjectAdminUiSetting::class,
        'coupon_default_image_url' => ProjectAdminUiSetting::class,
        'frontend_logo_url' => ProjectAdminUiSetting::class,
        'navigation_header_image_url' => ProjectAdminUiSetting::class,
        'navigation_header_background_color' => ProjectAdminUiSetting::class,
        'home_image_url' => ProjectAdminUiSetting::class,
        'home_image_urls' => ProjectAdminUiSetting::class,
        'home_menu_layout' => ProjectAdminUiSetting::class,
        'home_content' => ProjectAdminUiSetting::class,
        'home_show_member_card' => ProjectAdminUiSetting::class,
        'native_chrome_colors' => ProjectAdminUiSetting::class,
        'product_display_layout' => ProjectAdminUiSetting::class,
        'native_navigation_items' => ProjectAdminUiSetting::class,
        'member_show_rank_progress' => ProjectAdminUiSetting::class,
        'member_show_point_history' => ProjectAdminUiSetting::class,
        'member_show_coupon_activity' => ProjectAdminUiSetting::class,
        'frontend_header_text' => ProjectAdminUiSetting::class,
        'shop_hero_headline' => ProjectAdminUiSetting::class,
        'shop_hero_subcopy' => ProjectAdminUiSetting::class,
        'shop_hero_image_url' => ProjectAdminUiSetting::class,
        'shop_featured_product_ids' => ProjectAdminUiSetting::class,
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
        'use_stamps' => ProjectMemberRankSetting::class,
        'use_transaction_stamps' => ProjectMemberRankSetting::class,
        'use_points' => ProjectMemberRankSetting::class,
        'point_usage_scope' => ProjectMemberRankSetting::class,
        'member_rank_aggregation_type' => ProjectMemberRankSetting::class,
        'member_rank_period_days' => ProjectMemberRankSetting::class,
        'is_rank_down_enabled' => ProjectMemberRankSetting::class,
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

    public function getValue(string $key, $default = null, ?int $projectId = null)
    {
        $projectId = AdminContext::resolveProjectId($projectId);
        $modelClass = self::KEY_TO_MODEL[$key] ?? null;
        if ($modelClass === null) {
            return $default;
        }

        $store = $this->storeContext->store;
        if (($modelClass === ProjectBusinessSetting::class || in_array($key, ['order_mode', 'order_line_notify_enabled', 'order_ready_line_notify_enabled'], true)) && $store !== null && (int) $store->project_id === $projectId && array_key_exists($key, $store->settings ?? [])) {
            return $store->settings[$key];
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

    public function setValue(string $key, $value, string $type = 'string', ?int $projectId = null): void
    {
        $projectId = AdminContext::resolveProjectId($projectId);
        $modelClass = self::KEY_TO_MODEL[$key] ?? null;
        if ($modelClass === null) {
            return;
        }

        $context = $this->storeContext;
        if (($modelClass === ProjectBusinessSetting::class || in_array($key, ['order_mode', 'order_line_notify_enabled', 'order_ready_line_notify_enabled'], true)) && $context->projectStoreCount > 1 && $context->store !== null && (int) $context->store->project_id === $projectId) {
            if ($type === 'json' && is_string($value)) {
                $value = json_decode($value, true) ?? $value;
            }
            $context->store->update(['settings' => [...($context->store->settings ?? []), $key => $value]]);

            return;
        }
        if ($context->bound && $context->projectStoreCount > 1 && $context->admin !== null && ! $context->admin->isSystemAdmin() && ! $context->admin->isProjectAdmin()) {
            abort(403, 'プロジェクト共通設定は本部管理者が変更してください。');
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
}
