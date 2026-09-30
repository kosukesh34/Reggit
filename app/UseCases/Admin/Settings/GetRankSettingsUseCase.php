<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\RankSettings;
use App\Models\StampCard;
use App\UseCases\Admin\Settings\Data\RankSettingsView;

class GetRankSettingsUseCase
{
    public function __construct(
        private RankSettings $shopSettingRepository,
    ) {}

    public function handle(int $projectId, bool $ensureStampCard = true): RankSettingsView
    {
        if ($ensureStampCard) {
            $this->ensureDefaultStampCardExists($projectId);
        }

        return new RankSettingsView(
            $this->shopSettingRepository->getMemberRankSettings($projectId),
            $this->shopSettingRepository->getRankBasis($projectId),
            $this->shopSettingRepository->getPointEarnRate($projectId),
            $this->shopSettingRepository->getPointsValidDays($projectId),
            $this->shopSettingRepository->getPointsPerOrder($projectId),
            $this->shopSettingRepository->getStampEarnPerAmount($projectId),
            $this->shopSettingRepository->getRankAggregationType($projectId),
            $this->shopSettingRepository->getRankPeriodDays($projectId),
            $this->shopSettingRepository->getIsRankDownEnabled($projectId),
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
