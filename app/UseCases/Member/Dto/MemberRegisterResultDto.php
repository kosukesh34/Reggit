<?php

namespace App\UseCases\Member\Dto;

class MemberRegisterResultDto
{
    public function __construct(
        public MemberDataDto $member,
        public bool $alreadyRegistered
    ) {
    }
}
