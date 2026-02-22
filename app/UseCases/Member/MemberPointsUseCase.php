<?php

namespace App\UseCases\Member;

use App\Models\PointTransaction;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberPointsOutput;
use Illuminate\Http\Request;

class MemberPointsUseCase
{
    public function __construct(private UserService $userService)
    {
    }

    public function handle(Request $request): MemberPointsOutput
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        if ($member === null) {
            return new MemberPointsOutput(false, 0, collect());
        }

        $transactions = PointTransaction::where('member_id', $member->id)
            ->with('order')
            ->latest()
            ->take(20)
            ->get();

        return new MemberPointsOutput(true, (int) $member->points, $transactions);
    }
}
