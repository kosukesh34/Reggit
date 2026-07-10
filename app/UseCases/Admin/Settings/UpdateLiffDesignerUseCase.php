<?php

namespace App\UseCases\Admin\Settings;

use App\Services\Admin\LiffSettingsService;
use App\UseCases\Admin\Settings\Data\UpdateLiffDesignerInput;

class UpdateLiffDesignerUseCase
{
    public function __construct(
        private LiffSettingsService $liffSettingsService,
    ) {}

    public function handle(int $projectId, UpdateLiffDesignerInput $input): void
    {
        $this->liffSettingsService->setTabLayouts($input->layouts, $projectId);
        $this->liffSettingsService->setCustomCss($input->customCss, $projectId);

        if ($input->tabSettings !== null) {
            $this->liffSettingsService->setTabSettings($input->tabSettings, $projectId);
        }

        if ($input->menuItems !== null && $input->menuItems !== []) {
            $this->liffSettingsService->setFrontendMenuItems($input->menuItems, $projectId);
        }
    }
}
