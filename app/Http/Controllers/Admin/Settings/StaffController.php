<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Support\StaffManagementAuthorizer;
use App\Http\Support\StaffPermissionCatalog;
use App\UseCases\Admin\Settings\GetStaffListUseCase;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StaffController extends AdminBaseController
{
    public function __construct(
        private GetStaffListUseCase $getStaffListUseCase,
        private StaffManagementAuthorizer $staffManagementAuthorizer,
        private StaffPermissionCatalog $staffPermissionCatalog,
    ) {}

    public function index(Request $request): View
    {
        $this->staffManagementAuthorizer->authorize($request);
        $projectId = $this->resolveAdminProjectId();
        $staff = $this->getStaffListUseCase->handle($projectId)->filter(fn ($member) => $this->staffManagementAuthorizer->canManageTarget($request, $member, $projectId));

        return view('admin.settings.staff', [
            'staff' => $staff,
            'assignableStores' => $this->staffManagementAuthorizer->assignableStores($request, $projectId)->get(),
            'canAssignManager' => $request->user('admin')->isProjectAdmin() || $request->user('admin')->isSystemAdmin(),
            'permissionGroups' => $this->staffPermissionCatalog->groups(),
            'permissionLabels' => $this->staffPermissionCatalog->labels(),
        ]);
    }
}
