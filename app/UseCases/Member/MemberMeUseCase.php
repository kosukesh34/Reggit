<?php

namespace App\UseCases\Member;

use App\Models\User;
use App\Services\Member\MemberDataReader;
use App\Services\Member\MemberRegistrar;
use App\UseCases\Member\Data\MemberData;

class MemberMeUseCase
{
    public function __construct(
        private MemberRegistrar $memberRegistrar,
        private MemberDataReader $memberData,
    ) {}

    public function handle(User $user): MemberData
    {
        $this->memberRegistrar->ensureMemberWhenRegistrationOptional($user);

        return new MemberData($this->memberData->getMemberData($user));
    }
}
