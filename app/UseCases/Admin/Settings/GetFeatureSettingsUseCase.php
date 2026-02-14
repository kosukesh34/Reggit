<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Dto\FeatureSettingsViewDto;

class GetFeatureSettingsUseCase
{
    public function handle(int $projectId): FeatureSettingsViewDto
    {
        return new FeatureSettingsViewDto(
            ShopSetting::getFeatureFlags($projectId),
            ShopSetting::FEATURE_FLAG_LABELS,
            ShopSetting::getAdminSidebarColor($projectId),
            ShopSetting::getAdminHeaderColor($projectId),
            ShopSetting::getAdminPrimaryColor($projectId),
            ShopSetting::getStampDefaultImageUrl($projectId),
            ShopSetting::getCouponDefaultImageUrl($projectId),
            ShopSetting::getFrontendLogoUrl($projectId),
            ShopSetting::getFrontendHeaderText($projectId)
        );
    }
}
