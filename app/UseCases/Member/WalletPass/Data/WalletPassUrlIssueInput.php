<?php

namespace App\UseCases\Member\WalletPass\Data;

use App\Models\User;

class WalletPassUrlIssueInput
{
    public function __construct(
        public readonly User $user,
        public readonly int $projectId,
        public readonly string $projectKey,
        public readonly string $requestOrigin,
    ) {}
}
