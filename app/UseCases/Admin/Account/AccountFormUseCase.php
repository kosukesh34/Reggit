<?php

namespace App\UseCases\Admin\Account;

use App\Models\AdminUser;
use App\Models\AdminUserPermission;
use App\Models\Project;
use App\UseCases\Admin\Account\Data\AccountFormView;
use App\UseCases\Admin\Data\RouteContext;

class AccountFormUseCase
{
    public function getCreateView(AdminUser $me, RouteContext $context): AccountFormView
    {
        if ($me->isStaff()) {
            abort(403);
        }

        $projects = $this->getAccessibleProjects($me);

        return new AccountFormView(
            null,
            $projects,
            $me->getCreatableRoles(),
            AdminUserPermission::ALL_PERMISSIONS,
            AdminUserPermission::PERMISSION_GROUPS,
            [],
            $me,
            $context->routePrefix,
            $context->params
        );
    }

    public function getEditView(AdminUser $me, int $id, RouteContext $context): AccountFormView
    {
        $adminUser = AdminUser::with('permissions')->findOrFail($id);

        if ($me->canEditUser($adminUser) === false) {
            abort(403, 'このアカウントを編集する権限がありません。');
        }

        $projects = $this->getAccessibleProjects($me);

        return new AccountFormView(
            $adminUser,
            $projects,
            $me->getCreatableRoles(),
            AdminUserPermission::ALL_PERMISSIONS,
            AdminUserPermission::PERMISSION_GROUPS,
            $adminUser->permissions->pluck('permission')->all(),
            $me,
            $context->routePrefix,
            $context->params
        );
    }

    private function getAccessibleProjects(AdminUser $me): \Illuminate\Database\Eloquent\Collection
    {
        if ($me->isSystemAdmin()) {
            return Project::where('is_active', true)->orderBy('name')->get();
        }
        $allowedIds = $me->getAllowedProjectIds();
        return Project::whereIn('id', $allowedIds)->where('is_active', true)->orderBy('name')->get();
    }
}
