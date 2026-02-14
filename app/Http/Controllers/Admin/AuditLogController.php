<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\UseCases\Admin\AuditLog\AuditLogIndexUseCase;
use App\UseCases\Admin\AuditLog\AuditLogShowUseCase;

class AuditLogController extends Controller
{
    public function __construct(
        private AuditLogIndexUseCase $indexUseCase,
        private AuditLogShowUseCase $showUseCase
    ) {
    }

    public function index()
    {
        $logs = $this->indexUseCase->handle();

        return view('admin.audit-logs.index', compact('logs'));
    }

    public function show(int $id)
    {
        $log = $this->showUseCase->handle($id);
        return view('admin.audit-logs.show', compact('log'));
    }
}
