<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Data\CouponsSettingsView;

class GetCouponsSettingsUseCase
{
    public function handle(int $projectId): CouponsSettingsView
    {
        $couponDefaultImageUrl = (string) (ShopSetting::getCouponDefaultImageUrl($projectId) ?? '');
        $featureCouponsEnabled = ShopSetting::isFeatureEnabled('feature_coupons_enabled', $projectId);

        return new CouponsSettingsView($couponDefaultImageUrl, $featureCouponsEnabled);
    }
}
