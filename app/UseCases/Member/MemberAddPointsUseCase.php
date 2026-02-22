<?php

namespace App\UseCases\Member;

use App\Services\UserService;
use App\UseCases\Member\Data\MemberPointsOutput;
use Illuminate\Http\Request;

class MemberAddPointsUseCase
{
    public function __construct(private UserService $userService)
    {
    }

    public function handle(Request $request, int $points, ?string $description): MemberPointsOutput
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        if ($member === null) {
            return new MemberPointsOutput(false, 0, collect());
        }

        $member->addPoints($points, $description);

        return new MemberPointsOutput(true, (int) $member->fresh()->points, collect());
    }
}
