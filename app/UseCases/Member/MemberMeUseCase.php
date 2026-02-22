<?php

namespace App\UseCases\Member;

use App\Services\MemberService;
use App\Services\UserService;
use App\UseCases\Member\Data\MemberData;
use Illuminate\Http\Request;

class MemberMeUseCase
{
    public function __construct(
        private MemberService $memberService,
        private UserService $userService
    ) {
    }

    public function handle(Request $request): MemberData
    {
        $user = $this->userService->getOrCreateUser($request);
        $data = $this->memberService->getMemberData($user);

        return new MemberData($data);
    }
}
