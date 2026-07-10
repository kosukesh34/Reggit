<?php

namespace App\UseCases\Admin\Auth\Data;

final class SystemAdminLoginInput
{
    public function __construct(
        public readonly string $loginId,
        public readonly string $password,
        public readonly bool $remember,
        public readonly AdminAuthAuditContext $auditContext,
    ) {}
}
