<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Data\UpdateLiffDesignerInput;

class UpdateLiffDesignerUseCase
{
    public function handle(int $projectId, UpdateLiffDesignerInput $input): void
    {
        ShopSetting::setLiffTabLayouts($input->layouts, $projectId);
        ShopSetting::setLiffCustomCss($input->customCss, $projectId);

        if ($input->tabSettings !== null) {
            ShopSetting::setLiffTabSettings($input->tabSettings, $projectId);
        }

        if ($input->menuItems !== null && $input->menuItems !== []) {
            ShopSetting::setFrontendMenuItems($this->fillMenuDefaults($input->menuItems), $projectId);
        }
    }

    /**
     * label / icon が空の項目はデフォルト値で補完する（frontend_menu_items の互換構造を維持）。
     *
     * @param  array<int, array{tab: string, label: string, icon: string, order: int, enabled: bool}>  $items
     * @return array<int, array<string, mixed>>
     */
    private function fillMenuDefaults(array $items): array
    {
        $defaults = collect(ShopSetting::defaultFrontendMenuItems())->keyBy('tab');

        return array_values(array_filter(array_map(function (array $item) use ($defaults) {
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
