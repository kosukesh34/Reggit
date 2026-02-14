<?php

namespace App\UseCases\Admin\Settings;

use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Dto\RankSettingsViewDto;

class GetRankSettingsUseCase
{
    public function handle(int $projectId): RankSettingsViewDto
    {
        return new RankSettingsViewDto(
            ShopSetting::getMemberRankSettings($projectId),
            ShopSetting::getRankBasis($projectId),
            ShopSetting::getPointEarnRate($projectId),
            ShopSetting::getPointsValidDays($projectId)
        );
    }
}
