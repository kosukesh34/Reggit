<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAuditLog;

class AuditLogController extends Controller
{
    public function index()
    {
        $logs = AdminAuditLog::with(['adminUser', 'project'])
            ->orderByDesc('id')
            ->paginate(30);

        return view('admin.audit-logs.index', compact('logs'));
    }

    public function show(int $id)
    {
        $log = AdminAuditLog::with(['adminUser', 'project'])->findOrFail($id);
        return view('admin.audit-logs.show', compact('log'));
    }
}
