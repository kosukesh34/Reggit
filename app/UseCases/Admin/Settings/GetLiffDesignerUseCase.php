<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Data\LiffDesignerView;

class GetLiffDesignerUseCase
{
    public function handle(int $projectId): LiffDesignerView
    {
        return new LiffDesignerView(
            ShopSetting::getLiffTabLayouts($projectId),
            ShopSetting::defaultLiffDesignerTemplates(),
            ShopSetting::getLiffCustomCss($projectId),
            ShopSetting::getLiffTabSettings($projectId),
            ShopSetting::getFeatureFlags($projectId),
            ShopSetting::getLineLiffId($projectId),
            ShopSetting::getLineLiffUrl($projectId),
            ShopSetting::getLineFriendAddUrl($projectId),
            $this->menuItems($projectId)
        );
    }

    /** タブメニュー項目（非表示タブ含む全件）を order 順で返す */
    private function menuItems(int $projectId): array
    {
        $defaults = ShopSetting::defaultFrontendMenuItems();
        $raw = ShopSetting::getValue('frontend_menu_items', null, $projectId);
        $byTab = collect(is_array($raw) ? $raw : [])->keyBy('tab');

        return collect($defaults)->map(function ($item) use ($byTab) {
            $saved = $byTab->get($item['tab']);

            return [
                'tab' => $item['tab'],
                'label' => $saved['label'] ?? $item['label'],
                'icon' => $saved['icon'] ?? $item['icon'],
                'order' => $saved['order'] ?? $item['order'],
                'enabled' => ! isset($saved['enabled']) || $saved['enabled'] !== false,
            ];
        })->sortBy('order')->values()->all();
    }
}
