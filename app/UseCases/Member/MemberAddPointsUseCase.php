<?php

namespace App\UseCases\Member;

use App\Services\UserService;
use App\UseCases\Member\Dto\MemberPointsResultDto;
use Illuminate\Http\Request;

class MemberAddPointsUseCase
{
    public function __construct(private UserService $userService)
    {
    }

    public function handle(Request $request, int $points, ?string $description): MemberPointsResultDto
    {
        $user = $this->userService->getOrCreateUser($request);
        $member = $user->member;

        if ($member === null) {
            return new MemberPointsResultDto(false, 0, collect());
        }

        $member->addPoints($points, $description);

        return new MemberPointsResultDto(true, (int) $member->fresh()->points, collect());
    }
}
