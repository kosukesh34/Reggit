<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Models\AdminUser;
use App\Models\AdminUserPermission;

class StaffController extends AdminBaseController
{
    public function index()
    {
        $this->authorizeManager();
        $projectId = $this->resolveAdminProjectId();
        $staff = AdminUser::query()->where('role', AdminUser::ROLE_STAFF)
            ->where(function ($query) use ($projectId) {
                $query->where('project_id', $projectId)
                    ->orWhereHas('projects', fn ($projects) => $projects->where('projects.id', $projectId));
            })->with('permissions')->orderBy('name')->get();

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
