<?php

namespace App\UseCases\Member;

use App\Models\ShopSetting;
use App\Services\MemberPointsExpiryCalculator;
use App\Services\MemberRankCalculator;
use App\UseCases\Member\Data\MemberPointCardView;
use App\UseCases\Member\Data\MemberUserInput;

class MemberPointCardUseCase
{
    public function __construct(
        private MemberRankCalculator $rankCalculator,
        private MemberPointsExpiryCalculator $expiryCalculator
    ) {}

    public function handle(MemberUserInput $input): MemberPointCardView
    {
        $member = $input->user->member;

        if ($member === null) {
            return new MemberPointCardView(true, []);
        }

        $rankInfo = $this->rankCalculator->getRankInfo($member);
        $expiryInfo = $this->expiryCalculator->getExpiryInfo($member);
        $projectId = $member->project_id ?? 1;
        $showPoints = ShopSetting::getShowPointsDisplay($projectId);
        $showRankProgress = ShopSetting::getShowRankProgress($projectId);
        $memberCardDesign = ShopSetting::getMemberCardDesign($projectId);
        $memberCardLogoUrl = ShopSetting::getFrontendLogoUrl($projectId);
        $memberCardDisplayType = ShopSetting::getMemberCardDisplayType($projectId);
        $memberCardCodeType = ShopSetting::getMemberCardCodeType($projectId);

        return new MemberPointCardView(false, [
            'member_number' => $member->member_number,
            'points' => $member->getDisplayPoints(),
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
            'member_card_design' => $memberCardDesign,
            'member_card_logo_url' => $memberCardLogoUrl,
            'member_card_display_type' => $memberCardDisplayType,
            'member_card_code_type' => $memberCardCodeType,
        ]);
    }
}
