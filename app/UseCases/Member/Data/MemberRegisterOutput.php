<?php

namespace App\UseCases\Member\Data;

/** 会員登録 UseCase の出力。不変。 */
class MemberRegisterOutput
{
    public function __construct(
        public MemberData $member,
        public bool $alreadyRegistered
    ) {
    }
}
