<?php

namespace App\UseCases\Member;

use App\Models\PointTransaction;
use App\Services\UserService;
use App\UseCases\Member\Dto\MemberPointsResultDto;
use Illuminate\Http\Request;

class MemberPointsUseCase
{
    public function __construct(private UserService $userService)
    {
    }

    public function handle(Request $request): MemberPointsResultDto
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        if ($member === null) {
            return new MemberPointsResultDto(false, 0, collect());
        }

        $transactions = PointTransaction::where('member_id', $member->id)
            ->with('order')
            ->latest()
            ->take(20)
            ->get();

        return new MemberPointsResultDto(true, (int) $member->points, $transactions);
    }
}
