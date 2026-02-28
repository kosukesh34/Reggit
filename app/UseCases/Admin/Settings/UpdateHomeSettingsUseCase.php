<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\Util\StrHelper;

class UpdateHomeSettingsUseCase
{
    /** @param array<int, array{tab?: string, label?: string, icon?: string, order?: int, enabled?: bool}> $rawTabs */
    public function handle(int $projectId, array $rawTabs): void
    {
        $defaults = collect(ShopSetting::defaultFrontendMenuItems())->keyBy('tab');
        $items = [];
        foreach ($rawTabs as $index => $row) {
            $tab = $row['tab'] ?? null;
            if (StrHelper::isEmptyStringOrNull($tab) || $defaults->has($tab) === false) {
                continue;
            }
            $items[] = array_merge($defaults->get($tab), [
                'tab' => $tab,
                'label' => $row['label'] ?? $defaults->get($tab)['label'],
                'icon' => $row['icon'] ?? $defaults->get($tab)['icon'],
                'order' => (int) ($row['order'] ?? $index),
                'enabled' => isset($row['enabled']) && (bool) $row['enabled'],
            ]);
        }
        usort($items, fn ($a, $b) => $a['order'] <=> $b['order']);
        ShopSetting::setFrontendMenuItems($items, $projectId);
    }
}
