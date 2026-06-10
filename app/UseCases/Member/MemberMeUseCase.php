<?php

namespace App\UseCases\Member;

use App\Services\MemberService;
use App\UseCases\Member\Data\MemberData;
use App\UseCases\Member\Data\MemberUserInput;

class MemberMeUseCase
{
    public function __construct(
        private MemberService $memberService,
    ) {}

    public function handle(MemberUserInput $input): MemberData
    {
        $data = $this->memberService->getMemberData($input->user);

        return new MemberData($data);
    }
}
