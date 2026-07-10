<?php

namespace App\UseCases\Member;

use App\Models\User;
use App\Services\MemberService;
use App\UseCases\Member\Data\MemberData;

class MemberMeUseCase
{
    public function __construct(
        private MemberService $memberService,
    ) {}

    public function handle(User $user): MemberData
    {
        return new MemberData($this->memberService->getMemberData($user));
    }
}
