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
            ShopSetting::getPointsValidDays($projectId)
        );
    }
}
