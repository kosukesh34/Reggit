<?php

namespace App\UseCases\Admin\AuditLog;

use App\Models\AdminAuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogIndexUseCase
{
    public function handle(?int $projectId = null, bool $systemContext = false): LengthAwarePaginator
    {
        $query = AdminAuditLog::with(['adminUser', 'project'])
            ->orderByDesc('id')
            ->limit(500);

        if ($systemContext === false && $projectId !== null) {
            $query->where('project_id', $projectId)
                ->where(function ($q) {
                    $q->whereNull('route_name')
                        ->orWhere('route_name', 'like', 'admin.%');
                });
        }

        return $query->paginate(30);
    }
}
