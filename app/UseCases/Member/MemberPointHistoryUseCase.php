<?php

namespace App\UseCases\Member;

use App\Models\PointTransaction;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberPointHistoryOutput;
use Illuminate\Http\Request;

class MemberPointHistoryUseCase
{
    public function __construct(private UserService $userService) {}

    public function handle(Request $request, int $perPage = 20): MemberPointHistoryOutput
    {
        $user = $this->userService->getOrCreateUser($request);
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
