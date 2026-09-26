<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Models\AdminUser;
use App\Models\AdminUserPermission;
use App\UseCases\Admin\Settings\GetStaffListUseCase;

class StaffController extends AdminBaseController
{
    public function __construct(private GetStaffListUseCase $getStaffListUseCase) {}

    public function index()
    {
        $this->authorizeManager();
        $projectId = $this->resolveAdminProjectId();
        $staff = $this->getStaffListUseCase->handle($projectId);

        return view('admin.settings.staff', [
            'staff' => $staff,
            'permissionGroups' => AdminUserPermission::PERMISSION_GROUPS,
            'permissionLabels' => AdminUserPermission::ALL_PERMISSIONS,
        ]);
    }

    private function authorizeManager(): void
    {
        abort_unless(auth('admin')->user()?->canCreateRole(AdminUser::ROLE_STAFF), 403);
    }
}
