<?php

namespace App\UseCases\Member;

use App\Models\PointTransaction;
use App\Models\User;
use App\Services\Member\MemberPointLedger;
use App\UseCases\Member\Data\MemberPointsOutput;

class MemberPointsUseCase
{
    public function __construct(private MemberPointLedger $pointLedger) {}

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

        return new MemberPointsOutput(true, $this->pointLedger->displayPoints($member), $transactions);
    }
}
