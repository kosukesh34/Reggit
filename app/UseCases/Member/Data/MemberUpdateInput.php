<?php

namespace App\UseCases\Member\Data;

use App\Models\User;

class MemberUpdateInput
{
    /** @param array<string, string|int|float|bool|array|null> $validated */
    public function __construct(
        public User $user,
        public array $validated,
    ) {}
}
