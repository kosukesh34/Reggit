<?php

namespace App\UseCases\Member;

use App\Services\Member\MemberProfileUpdater;
use App\UseCases\Member\Data\MemberData;
use App\UseCases\Member\Data\MemberUpdateInput;

class MemberUpdateUseCase
{
    public function __construct(
        private MemberProfileUpdater $profileUpdater,
    ) {}

    public function handle(MemberUpdateInput $input): MemberData
    {
        $data = $this->profileUpdater->updateProfile($input->user, $input->profilePayload);

        return new MemberData($data);
    }
}
