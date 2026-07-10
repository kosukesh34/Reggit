<?php

namespace App\UseCases\Member;

use App\Models\PointTransaction;
use App\Models\User;
use App\UseCases\Member\Data\MemberPointHistoryOutput;

class MemberPointHistoryUseCase
{
    public function handle(User $user, int $perPage = 20): MemberPointHistoryOutput
    {
        $member = $user->member;

        if ($member === null) {
            return new MemberPointHistoryOutput(true, null);
        }

        $transactions = PointTransaction::where('member_id', $member->id)
            ->with('order')
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return new MemberPointHistoryOutput(false, $transactions);
    }
}
