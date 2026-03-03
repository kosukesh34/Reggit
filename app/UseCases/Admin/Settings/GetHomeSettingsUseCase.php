<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;

class GetHomeSettingsUseCase
{
    public function handle(int $projectId): array
    {
        $defaults = ShopSetting::defaultFrontendMenuItems();
        $raw = ShopSetting::getValue('frontend_menu_items', null, $projectId);
        $byTab = collect(is_array($raw) ? $raw : [])->keyBy('tab');

        $tabs = collect($defaults)->map(function ($item) use ($byTab) {
            $saved = $byTab->get($item['tab']);

            return [
                'tab' => $item['tab'],
                'label' => $saved['label'] ?? $item['label'],
                'icon' => $saved['icon'] ?? $item['icon'],
                'order' => $saved['order'] ?? $item['order'],
                'enabled' => ! isset($saved['enabled']) || $saved['enabled'] !== false,
            ];
        })->sortBy('order')->values()->all();

        $availableIcons = collect($defaults)->pluck('icon')->unique()->values()->all();

        return ['tabs' => $tabs, 'availableIcons' => $availableIcons];
    }
}
