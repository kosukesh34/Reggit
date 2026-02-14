<?php

namespace App\UseCases\Member;

use App\Services\MemberService;
use App\Services\UserService;
use App\UseCases\Member\Dto\MemberDataDto;
use Illuminate\Http\Request;

class MemberMeUseCase
{
    public function __construct(
        private MemberService $memberService,
        private UserService $userService
    ) {
    }

    public function handle(Request $request): MemberDataDto
    {
        $user = $this->userService->getOrCreateUser($request);
        $data = $this->memberService->getMemberData($user);

        return new MemberDataDto($data);
    }
}
