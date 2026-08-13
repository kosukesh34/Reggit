<?php

namespace App\UseCases\Stamp\Data;

use App\Models\User;

class StampGrantByCodeInput
{
    public function __construct(
        public readonly User $user,
        public readonly string $token,
    ) {}
}
