<?php

namespace App\UseCases\Stamp\Data;

use App\Models\User;

class StampGrantInput
{
    public function __construct(
        public readonly User $user,
        public readonly int $projectId,
        public readonly int $stampCardId,
        public readonly string $nonce,
    ) {}
}
