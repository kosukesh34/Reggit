<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Util\AdminContext;
use App\UseCases\Admin\AuditLog\AuditLogIndexUseCase;
use App\UseCases\Admin\AuditLog\AuditLogShowUseCase;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    private AuditLogIndexUseCase $indexUseCase;
    private AuditLogShowUseCase $showUseCase;

    public function __construct(
        AuditLogIndexUseCase $indexUseCase,
        AuditLogShowUseCase $showUseCase
    ) {
        $this->indexUseCase = $indexUseCase;
        $this->showUseCase = $showUseCase;
    }

    public function index(Request $request)
    {
        $isSystemContext = $request->routeIs('system-admin.*');
        $projectId = $isSystemContext ? null : AdminContext::getCurrentProjectId();
        $logs = $this->indexUseCase->handle($projectId, $isSystemContext);

        return view('admin.audit-logs.index', compact('logs'));
    }

    public function show(Request $request, int $id)
    {
        $isSystemContext = $request->routeIs('system-admin.*');
        $projectId = $isSystemContext ? null : AdminContext::getCurrentProjectId();
        $log = $this->showUseCase->handle($id, $projectId, $isSystemContext);
        return view('admin.audit-logs.show', compact('log'));
    }
}
