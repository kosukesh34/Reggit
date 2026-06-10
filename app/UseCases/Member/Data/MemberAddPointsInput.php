<?php

namespace App\UseCases\Member\Data;

use App\Models\User;

class MemberAddPointsInput
{
    public function __construct(
        public User $user,
        public int $points,
        public ?string $description,
    ) {}
}
