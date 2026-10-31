<?php

namespace App\UseCases\Member;

use App\Domain\Settings\Repositories\MemberCardPreviewSettings;
use App\Models\User;
use App\Services\Member\MemberPointLedger;
use App\Services\MemberPointsExpiryCalculator;
use App\Services\MemberRankCalculator;
use App\UseCases\Member\Data\MemberPointCardView;
use App\Util\AdminContext;

class MemberPointCardUseCase
{
    public function __construct(
        private MemberRankCalculator $rankCalculator,
        private MemberPointsExpiryCalculator $expiryCalculator,
        private MemberCardPreviewSettings $shopSettingRepository,
        private MemberPointLedger $pointLedger,
    ) {}

    public function handle(User $user): MemberPointCardView
    {
        $member = $user->member;

        if ($member === null) {
            return new MemberPointCardView(true, []);
        }

        $rankInfo = $this->rankCalculator->getRankInfo($member);
        $expiryInfo = $this->expiryCalculator->getExpiryInfo($member);
        $projectId = AdminContext::resolveProjectId($member->project_id !== null ? (int) $member->project_id : null);
        $showPoints = $this->shopSettingRepository->getShowPointsDisplay($projectId)
            && $this->shopSettingRepository->getUsePoints($projectId);
        $showRankProgress = $this->shopSettingRepository->getShowRankProgress($projectId);
        $memberCardDesign = $this->shopSettingRepository->getMemberCardDesign($projectId);
        $memberCardLogoUrl = $this->shopSettingRepository->getFrontendLogoUrl($projectId);
        $memberCardDisplayType = $this->shopSettingRepository->getMemberCardDisplayType($projectId);
        $memberCardCodeType = $this->shopSettingRepository->getMemberCardCodeType($projectId);

        return new MemberPointCardView(false, [
            'member_number' => $member->member_number,
            'points' => $this->pointLedger->displayPoints($member),
            'rank' => $rankInfo['current_rank'] ?? null,
            'rank_label' => $rankInfo['current_rank_label'] ?? null,
            'rank_icon' => $rankInfo['current_rank_icon'] ?? null,
            'rank_background' => $rankInfo['current_rank_background'] ?? null,
            'rank_color' => $rankInfo['current_rank_color'] ?? null,
            'points_expiry' => $expiryInfo,
            'show_points_display' => $showPoints,
            'show_rank_progress' => $showRankProgress,
            'points_to_next_rank' => $rankInfo['points_to_next_rank'] ?? null,
            'next_rank_label' => $rankInfo['next_rank_label'] ?? null,
            'rank_basis' => $rankInfo['rank_basis'] ?? 'points',
            'rank_metric' => $rankInfo['rank_metric'] ?? null,
            'days_to_next_rank' => $rankInfo['days_to_next_rank'] ?? null,
            'days_required_for_next_rank' => $rankInfo['days_required_for_next_rank'] ?? null,
            'rank_aggregation_type' => $rankInfo['rank_aggregation_type'] ?? 'total',
            'member_card_design' => $memberCardDesign,
            'member_card_logo_url' => $memberCardLogoUrl,
            'member_card_display_type' => $memberCardDisplayType,
            'member_card_code_type' => $memberCardCodeType,
        ]);
    }
}
