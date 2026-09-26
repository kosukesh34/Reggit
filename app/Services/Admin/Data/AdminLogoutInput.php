<?php

namespace App\Services\Admin\Data;

final class AdminLogoutInput
{
    public function __construct(
        public readonly ?int $adminUserId,
        public readonly bool $isSystemAdminLogout,
        public readonly AdminAuthAuditContext $auditContext,
    ) {}
}
