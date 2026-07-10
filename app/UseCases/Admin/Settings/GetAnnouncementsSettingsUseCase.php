<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\UseCases\Admin\Settings\Data\AnnouncementsSettingsView;

class GetAnnouncementsSettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId): AnnouncementsSettingsView
    {
        $featureEnabled = $this->shopSettingRepository->isFeatureEnabled('feature_announcements_enabled', $projectId);
        $defaultPinned = (bool) $this->shopSettingRepository->getValue('announcement_default_pinned', false, $projectId);
        $defaultExpiryDays = (int) $this->shopSettingRepository->getValue('announcement_default_expiry_days', 0, $projectId);

        return new AnnouncementsSettingsView($featureEnabled, $defaultPinned, $defaultExpiryDays);
    }
}
