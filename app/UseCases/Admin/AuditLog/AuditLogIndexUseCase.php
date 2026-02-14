<?php

namespace App\UseCases\Admin\AuditLog;

use App\Models\AdminAuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogIndexUseCase
{
    public function handle(): LengthAwarePaginator
    {
        return AdminAuditLog::with(['adminUser', 'project'])
            ->orderByDesc('id')
            ->paginate(30);
    }
}
