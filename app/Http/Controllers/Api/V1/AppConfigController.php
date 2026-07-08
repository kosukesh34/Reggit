<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Support\LiffThemePresets;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ネイティブアプリ起動時に呼ばれるアプリ設定API。
 * タブ構成・機能フラグ・テーマ・デザイナー設定を返す。
 */
class AppConfigController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $projectKey = $request->query('project_key', 'default');
        $project = Project::where('project_key', $projectKey)->where('is_active', true)->first();
        $projectId = $project ? (int) $project->id : 1;

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

        // LIFFデザイナーのテーマ（_global）が設定されていればアクセント色等を優先する
        $tabSettings = ShopSetting::getLiffTabSettings($projectId);
        $global = is_array($tabSettings['_global'] ?? null) ? $tabSettings['_global'] : [];
        $themeVars = LiffThemePresets::cssVariablesFor($global);
        $accent = $themeVars['--primary-color'] ?? ($theme['primary'] ?? '#000000');
        $accentRgb = $themeVars['--primary-rgb'] ?? ($theme['primary_rgb'] ?? '0, 0, 0');

        return response()->json([
            'project_key' => $projectKey,
            'project_name' => $project ? (string) $project->name : config('app.name'),
            'tabs' => array_map(fn ($item) => [
                'key' => $item['tab'],
                'label' => $item['label'],
                'icon' => $item['icon'] ?? 'fa-circle',
                'sf_symbol' => self::sfSymbolForTab($item['tab']),
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
        ]);
    }

    private static function sfSymbolForTab(string $tab): string
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
