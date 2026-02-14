<?php

namespace App\UseCases\Admin\AuditLog;

use App\Models\AdminAuditLog;

class AuditLogShowUseCase
{
    public function handle(int $id): AdminAuditLog
    {
        return AdminAuditLog::with(['adminUser', 'project'])->findOrFail($id);
    }
}
