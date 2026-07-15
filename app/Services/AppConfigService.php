<?php

namespace App\Services;

use App\Models\ShopSetting;
use App\Repositories\Contracts\ProjectRepository;
use App\Support\LiffThemePresets;

/**
 * ネイティブアプリ向け app-config API のペイロードを組み立てる。
 */
class AppConfigService
{
    public function __construct(
        private ProjectRepository $projectRepository,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildForProjectKey(string $projectKey): array
    {
        $project = $this->projectRepository->findActiveByProjectKey($projectKey);
        $projectId = $project !== null ? (int) $project->id : 1;

        $featureFlags = ShopSetting::getFeatureFlags($projectId);
        $menuItems = ShopSetting::getFrontendMenuItems($projectId);

        $tabFeatureMap = [
            'home' => 'feature_home_enabled',
            'products' => 'feature_orders_enabled',
            'orders' => 'feature_orders_enabled',
            'stamps' => 'feature_stamps_enabled',
            'coupons' => 'feature_coupons_enabled',
            'queue' => 'feature_queue_enabled',
            'reservations' => 'feature_reservations_enabled',
            'member' => 'feature_members_enabled',
            'gallery' => 'feature_gallery_enabled',
        ];

        $enabledTabs = array_values(array_filter($menuItems, function ($item) use ($featureFlags, $tabFeatureMap) {
            $flagKey = $tabFeatureMap[$item['tab']] ?? null;

            return $flagKey === null || ($featureFlags[$flagKey] ?? true);
        }));

        $theme = ShopSetting::getLineThemeColors($projectId);
        $tabSettings = ShopSetting::getLiffTabSettings($projectId);
        $global = is_array($tabSettings['_global'] ?? null) ? $tabSettings['_global'] : [];
        $themeVars = LiffThemePresets::cssVariablesFor($global);
        $accent = $themeVars['--primary-color'] ?? ($theme['primary'] ?? '#000000');
        $accentRgb = $themeVars['--primary-rgb'] ?? ($theme['primary_rgb'] ?? '0, 0, 0');

        return [
            'project_key' => $projectKey,
            'project_name' => $project !== null ? (string) $project->name : config('app.name'),
            'tabs' => array_map(fn ($item) => [
                'key' => $item['tab'],
                'label' => $item['label'],
                'icon' => $item['icon'] ?? 'fa-circle',
                'sf_symbol' => $this->sfSymbolForTab($item['tab']),
            ], $enabledTabs),
            'feature_flags' => $featureFlags,
            'theme' => [
                'primary_color' => $accent,
                'primary_rgb' => $accentRgb,
                'theme_preset' => (string) ($global['theme_preset'] ?? ''),
                'surface_tone' => (string) ($global['surface_tone'] ?? ''),
                'card_style' => (string) ($global['card_style'] ?? ''),
                'button_shape' => (string) ($global['button_shape'] ?? ''),
                'density' => (string) ($global['density'] ?? ''),
                'tab_style' => (string) ($global['tab_style'] ?? ''),
                'background_style' => (string) ($global['background_style'] ?? ''),
                'bg_color' => (string) ($global['bg_color'] ?? ''),
                'bg_image_url' => (string) ($global['bg_image_url'] ?? ''),
                'bg_gradient_start' => (string) ($global['bg_gradient_start'] ?? ''),
                'bg_gradient_end' => (string) ($global['bg_gradient_end'] ?? ''),
                'bg_overlay_opacity' => isset($global['bg_overlay_opacity']) ? (int) $global['bg_overlay_opacity'] : null,
                'content_width' => (string) ($global['content_width'] ?? 'comfy'),
                'system_ui_style' => (string) ($global['system_ui_style'] ?? 'refined'),
                'system_card_style' => (string) ($global['system_card_style'] ?? 'soft'),
                'system_media_shape' => (string) ($global['system_media_shape'] ?? 'rounded'),
                'system_header_style' => (string) ($global['system_header_style'] ?? 'minimal'),
                'member_card_design' => (string) ($global['member_card_design'] ?? ''),
                'member_card_display_type' => (string) ($global['member_card_display_type'] ?? ''),
                'member_card_code_type' => (string) ($global['member_card_code_type'] ?? ''),
                'app_display_name' => (string) ($global['app_display_name'] ?? ''),
                'member_screen_design' => (string) ($global['member_screen_design'] ?? 'wallet'),
                'coupon_screen_design' => (string) ($global['coupon_screen_design'] ?? 'ticket'),
                'reservation_screen_design' => (string) ($global['reservation_screen_design'] ?? 'cards'),
                'activity_screen_design' => (string) ($global['activity_screen_design'] ?? 'cards'),
            ],
            'header' => [
                'logo_url' => ShopSetting::getFrontendLogoUrl($projectId),
                'text' => ShopSetting::getFrontendHeaderText($projectId),
            ],
            'liff_tab_layouts' => ShopSetting::getLiffTabLayouts($projectId),
            'liff_tab_settings' => ShopSetting::getLiffTabSettings($projectId),
        ];
    }

    private function sfSymbolForTab(string $tab): string
    {
        return match ($tab) {
            'home' => 'house.fill',
            'products' => 'fork.knife',
            'orders' => 'list.clipboard.fill',
            'stamps' => 'star.fill',
            'coupons' => 'ticket.fill',
            'queue' => 'person.3.fill',
            'member' => 'person.crop.circle.fill',
            'reservations' => 'calendar',
            'gallery' => 'photo.on.rectangle.angled',
            default => 'circle.fill',
        };
    }
}
