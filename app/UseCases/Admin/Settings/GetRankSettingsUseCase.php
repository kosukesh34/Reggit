<?php

namespace App\UseCases\Admin\Settings;

use App\Models\StampCard;
use App\Models\ShopSetting;
use App\UseCases\Admin\Settings\Data\RankSettingsView;
use Illuminate\Support\Collection;

class GetRankSettingsUseCase
{
    public function handle(int $projectId): RankSettingsView
    {
        $this->ensureDefaultStampCardExists($projectId);
        $stampCardsForSelect = StampCard::where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

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
            ShopSetting::getFrontendLogoUrl($projectId),
            ShopSetting::getMemberCardDisplayType($projectId),
            ShopSetting::getMemberCardCodeType($projectId),
            ShopSetting::getPointsPerOrder($projectId),
            ShopSetting::getStampEarnPerAmount($projectId),
            ShopSetting::getOrderStampCardId($projectId),
            $stampCardsForSelect
        );
    }

    /** プロジェクトにスタンプカードが1枚もない場合はデフォルトの1枚を作成する */
    private function ensureDefaultStampCardExists(int $projectId): void
    {
        if (StampCard::where('project_id', $projectId)->exists()) {
            return;
        }
        StampCard::create([
            'project_id' => $projectId,
            'name' => 'スタンプカード',
            'required_stamps' => 10,
            'reward_description' => null,
            'image_url' => null,
            'layout_style' => StampCard::LAYOUT_GRID,
            'valid_days' => null,
            'valid_until' => null,
            'reward_milestones' => [],
            'is_active' => true,
        ]);
    }
}
