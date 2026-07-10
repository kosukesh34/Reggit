<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\UseCases\Admin\Settings\Data\CouponsSettingsView;

class GetCouponsSettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId): CouponsSettingsView
    {
        $couponDefaultImageUrl = (string) ($this->shopSettingRepository->getCouponDefaultImageUrl($projectId) ?? '');
        $featureCouponsEnabled = $this->shopSettingRepository->isFeatureEnabled('feature_coupons_enabled', $projectId);

        return new CouponsSettingsView($couponDefaultImageUrl, $featureCouponsEnabled);
    }
}
