<?php

namespace App\Services\Admin;

use App\Models\ShopSetting;
use App\Support\LiffDesignerSanitizer;

/**
 * LIFF デザイナー・フロントメニュー設定の永続化と取得を担当する。
 */
class LiffSettingsService
{
    /**
     * @return array<string, string|int|float|bool|array|null>
     */
    public function getTabLayouts(int $projectId): array
    {
        return ShopSetting::getLiffTabLayouts($projectId);
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getDetailLayouts(int $projectId): array
    {
        return ShopSetting::getLiffDetailLayouts($projectId);
    }

    /**
     * @return array<string, string|int|float|bool|array|null>
     */
    public function getDefaultDesignerTemplates(): array
    {
        return ShopSetting::defaultLiffDesignerTemplates();
    }

    public function getCustomCss(int $projectId): string
    {
        return ShopSetting::getLiffCustomCss($projectId);
    }

    /**
     * @return array<string, string|int|float|bool|array|null>
     */
    public function getTabSettings(int $projectId): array
    {
        return ShopSetting::getLiffTabSettings($projectId);
    }

    /**
     * @return array<string, bool>
     */
    public function getFeatureFlags(int $projectId): array
    {
        return ShopSetting::getFeatureFlags($projectId);
    }

    public function getLineFriendAddUrl(int $projectId): string
    {
        return ShopSetting::getLineFriendAddUrl($projectId);
    }

    /**
     * タブメニュー項目（非表示タブ含む全件）を order 順で返す。
     *
     * @return array<int, array{tab: string, label: string, icon: string, order: int, enabled: bool}>
     */
    public function getMenuItemsForDesigner(int $projectId): array
    {
        $defaults = ShopSetting::defaultFrontendMenuItems();
        $raw = ShopSetting::getValue('frontend_menu_items', null, $projectId);
        $byTab = collect(is_array($raw) ? $raw : [])->keyBy('tab');

        $items = collect($defaults)->map(function (array $item) use ($byTab) {
            $saved = $byTab->get($item['tab']);
            $icon = (string) ($saved['icon'] ?? $item['icon']);

            return [
                'tab' => $item['tab'],
                'label' => $saved['label'] ?? $item['label'],
                'icon' => ShopSetting::normalizeFrontendMenuIcon($item['tab'], $icon),
                'order' => $saved['order'] ?? $item['order'],
                'enabled' => ! isset($saved['enabled']) || $saved['enabled'] !== false,
            ];
        });

        $linkItems = collect(is_array($raw) ? $raw : [])
            ->filter(fn ($item) => in_array($item['tab'] ?? '', LiffDesignerSanitizer::LINK_MENU_TABS, true)
                && isset($item['url']))
            ->map(function (array $item) {
                $item['open'] = 'embed';

                return $item;
            });

        return $items->concat($linkItems)->sortBy('order')->values()->all();
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $layouts
     */
    public function setTabLayouts(array $layouts, int $projectId): void
    {
        ShopSetting::setLiffTabLayouts($layouts, $projectId);
    }

    /**
     * @param  array<string, mixed>  $layouts
     */
    public function setDetailLayouts(array $layouts, int $projectId): void
    {
        ShopSetting::setLiffDetailLayouts($layouts, $projectId);
    }

    public function setCustomCss(string $css, int $projectId): void
    {
        ShopSetting::setLiffCustomCss($css, $projectId);
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $settings
     */
    public function setTabSettings(array $settings, int $projectId): void
    {
        ShopSetting::setLiffTabSettings($settings, $projectId);
    }

    /**
     * @param  array<int, array{tab: string, label: string, icon: string, order: int, enabled: bool}>  $items
     */
    public function setFrontendMenuItems(array $items, int $projectId): void
    {
        ShopSetting::setFrontendMenuItems($this->fillMenuDefaults($items), $projectId);
    }

    /**
     * @return array<int, array{id: string, name: string, layouts: array<string, array>, tab_settings: array<string, array>, created_at: string}>
     */
    public function getSavedScreens(int $projectId): array
    {
        return ShopSetting::getLiffSavedScreens($projectId);
    }

    /**
     * @param  array<int, array{id: string, name: string, layouts: array<string, array>, tab_settings: array<string, array>, created_at: string}>  $screens
     */
    public function setSavedScreens(array $screens, int $projectId): void
    {
        ShopSetting::setLiffSavedScreens($screens, $projectId);
    }

    public function getSavedScreensMax(): int
    {
        return ShopSetting::LIFF_SAVED_SCREENS_MAX;
    }

    /**
     * label / icon が空の項目はデフォルト値で補完する。
     *
     * @param  array<int, array{tab: string, label: string, icon: string, order: int, enabled: bool}>  $items
     * @return array<int, array<string, mixed>>
     */
    public function fillMenuDefaults(array $items): array
    {
        $defaults = collect(ShopSetting::defaultFrontendMenuItems())->keyBy('tab');

        return array_values(array_filter(array_map(function (array $item) use ($defaults) {
            // 外部ページタブはサニタイザーで label / icon 補完済みのためそのまま通す
            if (in_array($item['tab'] ?? '', LiffDesignerSanitizer::LINK_MENU_TABS, true)) {
                return $item;
            }
            $default = $defaults->get($item['tab']);
            if ($default === null) {
                return null;
            }
            if (($item['label'] ?? '') === '') {
                $item['label'] = $default['label'];
            }
            if (($item['icon'] ?? '') === '') {
                $item['icon'] = $default['icon'];
            }

            return $item;
        }, $items)));
    }
}
