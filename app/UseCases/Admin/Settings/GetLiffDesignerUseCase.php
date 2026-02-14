<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Dto\LiffDesignerViewDto;

class GetLiffDesignerUseCase
{
    public function handle(int $projectId): LiffDesignerViewDto
    {
        return new LiffDesignerViewDto(
            ShopSetting::getLiffTabLayouts($projectId),
            ShopSetting::defaultLiffDesignerTemplates(),
            ShopSetting::getLiffCustomCss($projectId),
            ShopSetting::getLiffTabSettings($projectId)
        );
    }
}
