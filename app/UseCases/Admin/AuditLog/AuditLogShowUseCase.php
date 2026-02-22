<?php

namespace App\UseCases\Admin\AuditLog;

use App\Models\AdminAuditLog;

class AuditLogShowUseCase
{
    public function handle(int $id, ?int $projectId = null, bool $systemContext = false): AdminAuditLog
    {
        $query = AdminAuditLog::with(['adminUser', 'project'])
            ->where('id', $id);

        if ($systemContext === false && $projectId !== null) {
            $query->where('project_id', $projectId)
                ->where(function ($q) {
                    $q->whereNull('route_name')
                        ->orWhere('route_name', 'like', 'admin.%');
                });
        }

        return $query->firstOrFail();
    }
}
