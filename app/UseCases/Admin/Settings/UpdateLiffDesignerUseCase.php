<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\Services\Line\LineLiffAdminService;
use App\UseCases\Admin\Settings\Data\UpdateLiffDesignerInput;

class UpdateLiffDesignerUseCase
{
    public function __construct(
        private readonly LineLiffAdminService $lineLiffAdminService
    ) {}

    public function handle(int $projectId, UpdateLiffDesignerInput $input): void
    {
        ShopSetting::setLiffTabLayouts($input->layouts, $projectId);
        ShopSetting::setLiffCustomCss($input->customCss, $projectId);
        ShopSetting::setLineLiffId($input->liffId, $projectId);
        ShopSetting::setLineLiffUrl($input->liffUrl, $projectId);
        ShopSetting::setLineFriendAddUrl($input->lineFriendAddUrl, $projectId);

        if ($input->tabSettings !== null) {
            ShopSetting::setLiffTabSettings($input->tabSettings, $projectId);
        }

        if ($input->menuItems !== null && $input->menuItems !== []) {
            ShopSetting::setFrontendMenuItems($this->fillMenuDefaults($input->menuItems), $projectId);
        }

        $this->syncLiffModuleMode($input->liffId);
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

    private function syncLiffModuleMode(string $liffId): void
    {
        if (! config('line.auto_enable_liff_module_mode', true)) {
            return;
        }
        if (trim($liffId) === '') {
            return;
        }
        $this->lineLiffAdminService->hideActionMenu($liffId);
    }
}
