<?php

namespace App\Services\Admin;

use App\Models\Member;
use App\Services\MemberPointsExpiryCalculator;
use App\Services\MemberRankCalculator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MemberAdminService
{
    public function getPaginatedList(int $perPage = 20): LengthAwarePaginator
    {
        return Member::with('user')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findOrFail(int $id): Member
    {
        $member = Member::with(['user', 'pointTransactions.order'])
            ->findOrFail($id);

        $rankCalculator = app(MemberRankCalculator::class);
        $expiryCalculator = app(MemberPointsExpiryCalculator::class);
        $rankInfo = $rankCalculator->getRankInfo($member);

        $member->setAttribute('rank_label', $rankInfo['current_rank_label'] ?? $member->rank);
        $member->setAttribute('points_expiry', $expiryCalculator->getExpiryInfo($member));

        return $member;
    }
}
