<?php

namespace App\Services;

use App\Domain\Settings\Repositories\AppConfigSettings;
use App\Domain\Settings\Repositories\RankSettings;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Repositories\Contracts\ProjectRepository;
use App\Support\HomeContent;
use App\Support\HomeImageUrls;
use App\Support\JapanPrefectures;
use App\Support\LiffDesignerSanitizer;
use App\Support\LiffThemePresets;
use Illuminate\Support\Collection;

/**
 * ネイティブアプリ向け app-config API のペイロードを組み立てる。
 */
class AppConfigService
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private AppConfigSettings $shopSettingRepository,
        private RankSettings $rankSettings,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildForProjectKey(string $projectKey): array
    {
        $project = $this->projectRepository->findActiveByProjectKey($projectKey);
        $projectId = $project !== null ? (int) $project->id : 1;
        $repo = $this->shopSettingRepository;
        $homeImageUrls = HomeImageUrls::forProject($repo, $projectId);

        $featureFlags = $repo->getFeatureFlags($projectId);
        $menuItems = $repo->getFrontendMenuItems($projectId);

        $tabFeatureMap = [
            'home' => 'feature_home_enabled',
            'products' => 'feature_orders_enabled',
            'shop' => 'feature_shop_enabled',
            'orders' => 'feature_orders_enabled',
            'stamps' => 'feature_stamps_enabled',
            'coupons' => 'feature_coupons_enabled',
            'tickets' => 'feature_tickets_enabled',
            'queue' => 'feature_queue_enabled',
            'reservations' => 'feature_reservations_enabled',
            'member' => 'feature_members_enabled',
            'gallery' => 'feature_gallery_enabled',
            'announcements' => 'feature_announcements_enabled',
        ];

        $enabledTabs = array_values(array_filter($menuItems, function ($item) use ($featureFlags, $tabFeatureMap) {
            if (($item['tab'] ?? null) === 'designlab') {
                return false;
            }
            $flagKey = $tabFeatureMap[$item['tab']] ?? null;

            return $flagKey === null || ($featureFlags[$flagKey] ?? true);
        }));

        // ネイティブの配置設定はホームメニューの表示設定とは独立している。
        // 明示的にフッターへ置いた会員証などをホームメニュー非表示だけで落とさない。
        $savedMenu = $repo->getValue('frontend_menu_items', null, $projectId);
        $savedMenuByTab = collect(is_array($savedMenu) ? $savedMenu : [])->keyBy('tab');
        $defaultMenu = collect($repo->defaultFrontendMenuItems());
        $nativeNavigationCandidates = $defaultMenu
            ->map(fn ($item) => array_merge($item, is_array($savedMenuByTab->get($item['tab'])) ? $savedMenuByTab->get($item['tab']) : []))
            ->concat(collect(LiffDesignerSanitizer::sanitizeMenuItems(is_array($savedMenu) ? $savedMenu : []))
                ->filter(fn ($item) => in_array($item['tab'], LiffDesignerSanitizer::LINK_MENU_TABS, true)))
            ->filter(function ($item) use ($featureFlags, $tabFeatureMap) {
                $tab = $item['tab'] ?? null;
                if ($tab === 'designlab') {
                    return false;
                }
                $flagKey = $tabFeatureMap[$tab] ?? null;

                return $flagKey === null || ($featureFlags[$flagKey] ?? true);
            })
            ->values()
            ->all();
        $nativeNavigationCandidates[] = ['tab' => 'profileEdit', 'label' => 'プロフィール編集', 'icon' => 'fa-user-pen'];
        $nativeNavigationCandidates[] = [
            'tab' => 'terms',
            'label' => '利用規約',
            'icon' => 'fa-file-contract',
            'url' => route('legal.terms', ['project_key' => $projectKey]),
        ];
        $nativeNavigationCandidates[] = [
            'tab' => 'privacy',
            'label' => 'プライバシーポリシー',
            'icon' => 'fa-user-shield',
            'url' => route('legal.privacy', ['project_key' => $projectKey]),
        ];
        $nativeNavigationByKey = collect($nativeNavigationCandidates)->keyBy('tab');
        $savedNativeNavigation = $repo->getValue('native_navigation_items', null, $projectId);
        $defaultNavigationKeys = array_column($enabledTabs, 'tab');
        $nativeNavigation = is_array($savedNativeNavigation) ? $savedNativeNavigation : [
            'header' => [],
            'footer' => array_slice($defaultNavigationKeys, 0, 5),
            'hamburger' => array_merge($defaultNavigationKeys, ['terms', 'privacy']),
        ];

        $theme = $repo->getLineThemeColors($projectId);
        $tabSettings = $repo->getLiffTabSettings($projectId);
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
                'url' => isset($item['url']) ? (string) $item['url'] : null,
                'open' => ($item['open'] ?? 'embed') === 'external' ? 'external' : 'embed',
            ], $enabledTabs),
            'navigation' => [
                'header' => $this->navigationItems($nativeNavigation['header'] ?? [], $nativeNavigationByKey, 1),
                'footer' => $this->navigationItems($nativeNavigation['footer'] ?? [], $nativeNavigationByKey, 5),
                'hamburger' => $this->navigationItems($nativeNavigation['hamburger'] ?? [], $nativeNavigationByKey),
            ],
            'feature_flags' => $featureFlags,
            // アプリ初回の機能案内用（1pt = 1円で利用、貯まり方は「円ごとに1pt」）
            'points_guide' => [
                'use_points' => $this->rankSettings->getUsePoints($projectId),
                'earn_yen_per_point' => max(1, $this->rankSettings->getPointEarnRate($projectId)),
                'usage_scope' => (string) $this->rankSettings->getValue('point_usage_scope', 'app', $projectId),
                'ranks' => array_values(array_map(
                    static fn (array $rank): array => ['label' => (string) $rank['label'], 'min_points' => (int) $rank['min_points'], 'color' => $rank['card_color'] ?? null],
                    array_filter($this->rankSettings->getMemberRankSettings($projectId), static fn (array $rank): bool => (bool) ($rank['is_active'] ?? true)),
                )),
            ],
            'shop_shipping_fee' => max(0, (int) $repo->getValue('shop_shipping_fee', 500, $projectId)),
            'shop_shipping_fees_by_prefecture' => $this->normalizePrefectureFees(
                $repo->getValue('shop_shipping_fees_by_prefecture', [], $projectId)
            ),
            'shop_prefectures' => JapanPrefectures::names(),
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
                'member_card_bg_style' => (string) ($global['member_card_bg_style'] ?? 'gradient'),
                'member_card_gloss' => (string) ($global['member_card_gloss'] ?? 'soft'),
                'member_card_surface' => (string) ($global['member_card_surface'] ?? 'plastic'),
                'app_display_name' => (string) ($global['app_display_name'] ?? ''),
                'member_screen_design' => (string) ($global['member_screen_design'] ?? 'wallet'),
                'coupon_screen_design' => (string) ($global['coupon_screen_design'] ?? 'ticket'),
                'coupon_media_style' => (string) ($global['coupon_media_style'] ?? 'image'),
                'coupon_show_available' => ($global['coupon_show_available'] ?? true) !== false,
                'coupon_show_history' => ($global['coupon_show_history'] ?? true) !== false,
                'products_group_by_category' => ($global['products_group_by_category'] ?? true) !== false,
                'stamp_screen_design' => (string) ($global['stamp_screen_design'] ?? 'grid_card'),
                'stamp_slot_style' => (string) ($global['stamp_slot_style'] ?? 'circle'),
                'stamp_filled_style' => (string) ($global['stamp_filled_style'] ?? 'image'),
                'reservation_screen_design' => (string) ($global['reservation_screen_design'] ?? 'cards'),
                'activity_screen_design' => (string) ($global['activity_screen_design'] ?? 'cards'),
                'require_membership' => ($global['require_membership'] ?? true) !== false,
            ],
            'native_chrome_colors' => array_intersect_key((array) $repo->getValue('native_chrome_colors', [], $projectId), array_flip(['header_icon', 'footer_background', 'footer_icon', 'footer_active_icon', 'footer_active_background', 'primary_button_background', 'primary_button_text', 'secondary_button_background', 'secondary_button_text'])),
            'header' => [
                'logo_url' => $repo->getFrontendLogoUrl($projectId),
                'image_url' => (string) $repo->getValue('navigation_header_image_url', '', $projectId),
                'background_color' => (string) $repo->getValue('navigation_header_background_color', '#FFFFFF', $projectId),
                'text' => trim((string) ($global['app_display_name'] ?? '')) !== ''
                    ? (string) $global['app_display_name']
                    : $repo->getFrontendHeaderText($projectId),
            ],
            'home' => array_merge(HomeContent::forProject($repo, $projectId), [
                'show_member_card' => (bool) $repo->getValue('home_show_member_card', true, $projectId),
                'menu_layout' => (string) $repo->getValue('home_menu_layout', 'grid_3x2', $projectId),
                'image_url' => $homeImageUrls[0] ?? '',
                'image_urls' => $homeImageUrls,
            ]),
            'products' => [
                'display_layout' => (string) $repo->getValue('product_display_layout', 'list_with_image', $projectId),
            ],
            'member_screen' => [
                'style' => in_array($global['member_page_style'] ?? '', ['classic', 'wallet', 'minimal', 'ticket'], true) ? $global['member_page_style'] : 'classic',
                'show_rank_progress' => (bool) $repo->getValue('member_show_rank_progress', true, $projectId),
                'show_point_history' => (bool) $repo->getValue('member_show_point_history', true, $projectId),
                'show_coupon_activity' => (bool) $repo->getValue('member_show_coupon_activity', true, $projectId),
            ],
            'realtime' => $this->nativeRealtimeConfig(),
            'line' => [
                'liff_id' => $repo->getLineLiffId($projectId),
                'liff_url' => $repo->getLineLiffUrl($projectId),
                'friend_add_url' => $repo->getLineFriendAddUrl($projectId),
            ],
            'liff_tab_layouts' => $repo->getLiffTabLayouts($projectId),
            'liff_tab_settings' => $repo->getLiffTabSettings($projectId),
            'liff_detail_layouts' => $repo->getLiffDetailLayouts($projectId),
            'queue_require_membership' => $repo->getQueueRequireMembership($projectId),
            'member_registration_mode' => $repo->getMemberRegistrationMode($projectId),
        ];
    }

    /** @return array{websocket_url: string} */
    private function nativeRealtimeConfig(): array
    {
        if (config('broadcasting.default') !== 'reverb') {
            return ['websocket_url' => ''];
        }

        $key = trim((string) config('broadcasting.connections.reverb.key', ''));
        $clientHost = trim((string) config('broadcasting.client_ws_host', ''));
        // The tunnel script publishes its current public host here. LIFF uses
        // this same fallback; native clients must not connect to Xserver's
        // application origin, which does not proxy WebSocket upgrades.
        if ($clientHost === '') {
            $hostFile = storage_path('app/reverb-ws-host');
            if (is_file($hostFile)) {
                $clientHost = trim((string) file_get_contents($hostFile));
            }
        }
        $appUrl = (string) config('app.url', '');
        $host = $clientHost !== '' ? $clientHost : (string) parse_url($appUrl, PHP_URL_HOST);
        if ($key === '' || $host === '') {
            return ['websocket_url' => ''];
        }

        $secure = $clientHost !== '' || parse_url($appUrl, PHP_URL_SCHEME) === 'https';
        $port = $clientHost !== '' ? null : parse_url($appUrl, PHP_URL_PORT);
        $authority = $host.(is_int($port) ? ':'.$port : '');

        return ['websocket_url' => ($secure ? 'wss' : 'ws').'://'.$authority.'/app/'.rawurlencode($key).'?protocol=7&client=reggit-skip&version=1.0'];
    }

    private function sfSymbolForTab(string $tab): string
    {
        return match ($tab) {
            'home' => 'house.fill',
            'products' => 'fork.knife',
            'shop' => 'bag.fill',
            'orders' => 'list.clipboard.fill',
            'stamps' => 'star.fill',
            'designlab' => 'dice.fill',
            'coupons' => 'ticket.fill',
            'tickets' => 'ticket',
            'queue' => 'person.3.fill',
            'member', 'profileEdit' => 'person.crop.circle.fill',
            'reservations' => 'calendar',
            'gallery' => 'photo.on.rectangle.angled',
            'announcements' => 'megaphone.fill',
            'terms' => 'doc.text.fill',
            'privacy' => 'hand.raised.fill',
            default => 'circle.fill',
        };
    }

    /**
     * @param  array<mixed>  $keys
     * @param  Collection<string, array<string, mixed>>  $candidates
     * @return list<array<string, mixed>>
     */
    private function navigationItems(array $keys, $candidates, ?int $limit = null): array
    {
        $items = collect($keys)
            ->filter(fn ($key) => is_string($key) && $candidates->has($key))
            ->unique()
            ->map(function (string $key) use ($candidates) {
                $item = $candidates->get($key);

                return [
                    'key' => $key,
                    'label' => (string) ($item['label'] ?? $key),
                    'icon' => ShopSettingStore::normalizeFrontendMenuIcon($key, (string) ($item['icon'] ?? 'fa-circle')),
                    'sf_symbol' => $this->sfSymbolForTab($key),
                    'url' => isset($item['url']) ? (string) $item['url'] : null,
                    'open' => ($item['open'] ?? 'embed') === 'external' ? 'external' : 'embed',
                ];
            })
            ->values();

        if ($limit !== null) {
            $items = $items->take($limit)->values();
        }

        return $items->all();
    }

    /**
     * @return array<string, int>
     */
    private function normalizePrefectureFees(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }
        $fees = [];
        foreach ($raw as $prefecture => $fee) {
            if (! is_string($prefecture) || ! is_numeric($fee)) {
                continue;
            }
            $fees[$prefecture] = max(0, (int) $fee);
        }

        return $fees;
    }
}
