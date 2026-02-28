<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Data\RankSettingsView;

class GetRankSettingsUseCase
{
    public function handle(int $projectId): RankSettingsView
    {
        return new RankSettingsView(
            ShopSetting::getMemberRankSettings($projectId),
            ShopSetting::getRankBasis($projectId),
            ShopSetting::getPointEarnRate($projectId),
            ShopSetting::getPointsValidDays($projectId),
            ShopSetting::getMemberCodeType($projectId),
            ShopSetting::getMemberCodeFormat($projectId),
            ShopSetting::getMemberCodeLength($projectId),
            ShopSetting::getMemberCodePrefix($projectId),
            ShopSetting::getShowPointsDisplay($projectId),
            ShopSetting::getShowRankProgress($projectId),
            ShopSetting::getShowMemberCardForGuest($projectId),
            ShopSetting::getMemberCardDesign($projectId),
            ShopSetting::getFrontendLogoUrl($projectId)
        );
    }
}
