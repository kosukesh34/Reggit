<?php

namespace App\UseCases\Admin\Account\Dto;

class AccountUpdateInputDto
{
    /** @param array<string, mixed> $accountPayload */
    /** @param array<int, int> $projectIds */
    /** @param array<int, string> $permissions */
    public function __construct(
        public array $accountPayload,
        public array $projectIds,
        public array $permissions,
        public ?string $password,
        public bool $passwordFilled
    ) {
    }
}
