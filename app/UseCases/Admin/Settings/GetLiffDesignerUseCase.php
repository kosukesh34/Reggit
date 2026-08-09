<?php

namespace App\UseCases\Admin\Settings;

use App\Services\Admin\LiffSettingsService;
use App\Support\LiffDesignerPreviewData;
use App\UseCases\Admin\Settings\Data\LiffDesignerView;

class GetLiffDesignerUseCase
{
    public function __construct(
        private LiffSettingsService $liffSettingsService,
    ) {}

    public function handle(int $projectId): LiffDesignerView
    {
        return new LiffDesignerView(
            $this->liffSettingsService->getTabLayouts($projectId),
            $this->liffSettingsService->getDefaultDesignerTemplates(),
            $this->liffSettingsService->getCustomCss($projectId),
            $this->liffSettingsService->getTabSettings($projectId),
            $this->liffSettingsService->getFeatureFlags($projectId),
            $this->liffSettingsService->getLineFriendAddUrl($projectId),
            $this->liffSettingsService->getMenuItemsForDesigner($projectId),
            $this->liffSettingsService->getDetailLayouts($projectId),
            LiffDesignerPreviewData::forProject($projectId),
        );
    }
}
