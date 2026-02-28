<?php

namespace App\UseCases\Member;

use App\Services\MemberPointsExpiryCalculator;
use App\Services\MemberRankCalculator;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberPointCardView;
use Illuminate\Http\Request;

class MemberPointCardUseCase
{
    public function __construct(
        private UserService $userService,
        private MemberRankCalculator $rankCalculator,
        private MemberPointsExpiryCalculator $expiryCalculator
    ) {
    }

    public function handle(Request $request): MemberPointCardView
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        if ($member === null) {
            return new MemberPointCardView(true, []);
        }

        $rankInfo = $this->rankCalculator->getRankInfo($member);
        $expiryInfo = $this->expiryCalculator->getExpiryInfo($member);
        $projectId = $member->project_id ?? 1;
        $showPoints = \App\Models\ShopSetting::getShowPointsDisplay($projectId);
        $showRankProgress = \App\Models\ShopSetting::getShowRankProgress($projectId);
        $memberCardDesign = \App\Models\ShopSetting::getMemberCardDesign($projectId);
        $memberCardLogoUrl = \App\Models\ShopSetting::getFrontendLogoUrl($projectId);

        return new MemberPointCardView(false, [
            'member_number' => $member->member_number,
            'points' => (int) $member->points,
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
        ]);
    }
}
