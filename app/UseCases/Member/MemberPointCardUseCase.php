<?php

namespace App\UseCases\Member;

use App\Services\MemberPointsExpiryCalculator;
use App\Services\MemberRankCalculator;
use App\Services\UserService;
use App\UseCases\Member\Dto\MemberPointCardDto;
use Illuminate\Http\Request;

class MemberPointCardUseCase
{
    public function __construct(
        private UserService $userService,
        private MemberRankCalculator $rankCalculator,
        private MemberPointsExpiryCalculator $expiryCalculator
    ) {
    }

    public function handle(Request $request): MemberPointCardDto
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        if ($member === null) {
            return new MemberPointCardDto(true, []);
        }

        $rankInfo = $this->rankCalculator->getRankInfo($member);
        $expiryInfo = $this->expiryCalculator->getExpiryInfo($member);

        return new MemberPointCardDto(false, [
            'member_number' => $member->member_number,
            'points' => (int) $member->points,
            'rank' => $rankInfo['current_rank'] ?? null,
            'rank_label' => $rankInfo['current_rank_label'] ?? null,
            'rank_icon' => $rankInfo['current_rank_icon'] ?? null,
            'rank_background' => $rankInfo['current_rank_background'] ?? null,
            'points_expiry' => $expiryInfo,
        ]);
    }
}
