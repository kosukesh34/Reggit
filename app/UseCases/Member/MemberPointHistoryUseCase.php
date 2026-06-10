<?php

namespace App\UseCases\Member;

use App\Models\PointTransaction;
use App\UseCases\Member\Data\MemberPointHistoryInput;
use App\UseCases\Member\Data\MemberPointHistoryOutput;

class MemberPointHistoryUseCase
{
    public function handle(MemberPointHistoryInput $input): MemberPointHistoryOutput
    {
        $member = $input->user->member;

        if ($member === null) {
            return new MemberPointHistoryOutput(true, null);
        }

        $transactions = PointTransaction::where('member_id', $member->id)
            ->with('order')
            ->orderByDesc('created_at')
            ->paginate($input->perPage);

        return new MemberPointHistoryOutput(false, $transactions);
    }
}
