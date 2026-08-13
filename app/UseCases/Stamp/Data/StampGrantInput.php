<?php

namespace App\UseCases\Stamp\Data;

use App\Enums\StampGrantLimit;
use App\Models\User;

class StampGrantInput
{
    public function __construct(
        public readonly User $user,
        public readonly int $projectId,
        public readonly int $stampCardId,
        public readonly string $nonce,
        public readonly int $amount = 1,
        public readonly string $limit = StampGrantLimit::PER_MEMBER,
        public readonly ?int $expiresAt = null,
        public readonly int $quota = 1,
    ) {}
}
