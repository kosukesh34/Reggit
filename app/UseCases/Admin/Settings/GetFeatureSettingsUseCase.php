<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Data\FeatureSettingsView;

class GetFeatureSettingsUseCase
{
    public function handle(int $projectId): FeatureSettingsView
    {
        return new FeatureSettingsView(
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
