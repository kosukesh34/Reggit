<?php

namespace App\UseCases\Member;

use App\Services\MemberService;
use App\UseCases\Member\Data\MemberData;
use App\UseCases\Member\Data\MemberUpdateInput;

class MemberUpdateUseCase
{
    public function __construct(
        private MemberService $memberService,
    ) {}

    public function handle(MemberUpdateInput $input): MemberData
    {
        $data = $this->memberService->updateProfile($input->user, $input->profilePayload);

        return new MemberData($data);
    }
}
