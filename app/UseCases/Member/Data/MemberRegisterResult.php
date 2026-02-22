<?php

namespace App\UseCases\Member\Data;

/** 会員登録 UseCase の実行結果。不変。 */
class MemberRegisterResult
{
    public function __construct(
        public MemberData $member,
        public bool $alreadyRegistered
    ) {
    }
}
