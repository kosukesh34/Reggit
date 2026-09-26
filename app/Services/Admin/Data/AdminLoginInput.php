<?php

namespace App\Services\Admin\Data;

final class AdminLoginInput
{
    public function __construct(
        public readonly string $loginId,
        public readonly string $password,
        public readonly bool $remember,
        public readonly ?string $projectKey,
        public readonly string $requestPath,
        public readonly AdminAuthAuditContext $auditContext,
    ) {}
}
