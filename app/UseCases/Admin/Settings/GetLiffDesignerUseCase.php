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
            ShopSetting::getLineLiffUrl($projectId)
        );
    }
}
