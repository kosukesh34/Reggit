<?php

namespace App\UseCases\Member;

use App\Models\PointTransaction;
use App\Models\User;
use App\UseCases\Member\Data\MemberPointsOutput;

class MemberPointsUseCase
{
    public function handle(User $user): MemberPointsOutput
    {
        $member = $user->member;

        if ($member === null) {
            return new MemberPointsOutput(false, 0, collect());
        }

        $transactions = PointTransaction::where('member_id', $member->id)
            ->with('order')
            ->latest()
            ->take(20)
            ->get();

        return new MemberPointsOutput(true, $member->getDisplayPoints(), $transactions);
    }
}
