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

        $this->syncLiffModuleMode($input->liffId);
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
