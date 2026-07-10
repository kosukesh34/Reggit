<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Util\StrHelper;

class UpdateHomeSettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    /** @param array<int, array{tab?: string, label?: string, icon?: string, order?: int, enabled?: bool}> $rawTabs */
    public function handle(int $projectId, array $rawTabs): void
    {
        $defaults = collect($this->shopSettingRepository->defaultFrontendMenuItems())->keyBy('tab');
        $items = [];
        foreach ($rawTabs as $index => $row) {
            $tab = $row['tab'] ?? null;
            if (StrHelper::isEmptyStringOrNull($tab) || $defaults->has($tab) === false) {
                continue;
            }
            $default = $defaults->get($tab);
            $items[] = array_merge($default, [
                'tab' => $tab,
                'label' => StrHelper::isEmptyStringOrNull($row['label'] ?? null) ? $default['label'] : (string) $row['label'],
                'icon' => StrHelper::isEmptyStringOrNull($row['icon'] ?? null) ? $default['icon'] : (string) $row['icon'],
                'order' => (int) ($row['order'] ?? $index),
                'enabled' => isset($row['enabled']) && (bool) $row['enabled'],
            ]);
        }
        usort($items, fn ($a, $b) => $a['order'] <=> $b['order']);
        $this->shopSettingRepository->setFrontendMenuItems($items, $projectId);
    }
}
