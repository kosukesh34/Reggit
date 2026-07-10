<?php

namespace App\UseCases\Member\Data;

use App\Models\User;

/**
 * ポイント加算 UseCase への入力。不変。
 */
class MemberAddPointsInput
{
    public function __construct(
        public readonly User $user,
        public readonly int $points,
        public readonly ?string $description,
    ) {}
}
