<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\UseCases\Admin\Settings\Data\FeatureSettingsView;

class GetFeatureSettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId): FeatureSettingsView
    {
        return new FeatureSettingsView(
            $this->shopSettingRepository->getFeatureFlags($projectId),
            $this->shopSettingRepository->getFeatureFlagLabels(),
            $this->shopSettingRepository->getAdminSidebarColor($projectId),
            $this->shopSettingRepository->getAdminHeaderColor($projectId),
            $this->shopSettingRepository->getAdminPrimaryColor($projectId),
            $this->shopSettingRepository->getStampDefaultImageUrl($projectId),
            $this->shopSettingRepository->getCouponDefaultImageUrl($projectId),
            $this->shopSettingRepository->getFrontendLogoUrl($projectId),
            $this->shopSettingRepository->getFrontendHeaderText($projectId)
        );
    }
}
