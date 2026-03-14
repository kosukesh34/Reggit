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
        ShopSetting::setLineLiffId($input->liffId, $projectId);
        ShopSetting::setLineLiffUrl($input->liffUrl, $projectId);
        ShopSetting::setLineFriendAddUrl($input->lineFriendAddUrl, $projectId);

        if ($input->tabSettings !== null) {
            ShopSetting::setLiffTabSettings($input->tabSettings, $projectId);
        }
    }
}
