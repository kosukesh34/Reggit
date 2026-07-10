<?php

namespace App\UseCases\Member\Data;

use App\Models\User;

/**
 * 会員登録 UseCase への入力。検証済み。不変。
 *
 * @param  array<string, string|int|float|bool|array|null>  $profilePayload
 */
class MemberRegisterInput
{
    public function __construct(
        public readonly User $user,
        public readonly array $profilePayload,
    ) {}
}
