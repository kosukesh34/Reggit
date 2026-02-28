<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Data\AnnouncementsSettingsView;

class GetAnnouncementsSettingsUseCase
{
    public function handle(int $projectId): AnnouncementsSettingsView
    {
        $featureEnabled = ShopSetting::isFeatureEnabled('feature_announcements_enabled', $projectId);
        $defaultPinned = (bool) ShopSetting::getValue('announcement_default_pinned', false, $projectId);
        $defaultExpiryDays = (int) ShopSetting::getValue('announcement_default_expiry_days', 0, $projectId);

        return new AnnouncementsSettingsView($featureEnabled, $defaultPinned, $defaultExpiryDays);
    }
}
