<?php

namespace App\UseCases\Admin\Settings;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Models\StampCard;
use App\UseCases\Admin\Settings\Data\RankSettingsView;

class GetRankSettingsUseCase
{
    public function __construct(
        private ShopSettingRepository $shopSettingRepository,
    ) {}

    public function handle(int $projectId): RankSettingsView
    {
        $this->ensureDefaultStampCardExists($projectId);

        return new RankSettingsView(
            $this->shopSettingRepository->getMemberRankSettings($projectId),
            $this->shopSettingRepository->getRankBasis($projectId),
            $this->shopSettingRepository->getPointEarnRate($projectId),
            $this->shopSettingRepository->getPointsValidDays($projectId),
            $this->shopSettingRepository->getMemberCodeType($projectId),
            $this->shopSettingRepository->getMemberCodeFormat($projectId),
            $this->shopSettingRepository->getMemberCodeLength($projectId),
            $this->shopSettingRepository->getMemberCodePrefix($projectId),
            $this->shopSettingRepository->getShowPointsDisplay($projectId),
            $this->shopSettingRepository->getShowRankProgress($projectId),
            $this->shopSettingRepository->getShowMemberCardForGuest($projectId),
            $this->shopSettingRepository->getMemberCardDesign($projectId),
            $this->shopSettingRepository->getFrontendLogoUrl($projectId),
            $this->shopSettingRepository->getMemberCardDisplayType($projectId),
            $this->shopSettingRepository->getMemberCardCodeType($projectId),
            $this->shopSettingRepository->getPointsPerOrder($projectId),
            $this->shopSettingRepository->getStampEarnPerAmount($projectId),
            $this->shopSettingRepository->getUseStamps($projectId),
            $this->shopSettingRepository->getUseTransactionStamps($projectId),
            $this->shopSettingRepository->getUsePoints($projectId),
            $this->shopSettingRepository->getRankAggregationType($projectId),
            $this->shopSettingRepository->getRankPeriodDays($projectId),
            $this->shopSettingRepository->getIsRankDownEnabled($projectId),
            (bool) $this->shopSettingRepository->getValue('member_show_rank_progress', true, $projectId),
            (bool) $this->shopSettingRepository->getValue('member_show_point_history', true, $projectId),
            (bool) $this->shopSettingRepository->getValue('member_show_coupon_activity', true, $projectId),
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
