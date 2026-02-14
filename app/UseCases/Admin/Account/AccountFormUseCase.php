<?php

namespace App\UseCases\Admin\Account;

use App\Models\AdminUser;
use App\Models\AdminUserPermission;
use App\Models\Project;
use App\UseCases\Admin\Account\Dto\AccountFormViewDto;
use App\UseCases\Admin\Dto\RouteContextDto;

class AccountFormUseCase
{
    public function getCreateView(AdminUser $me, RouteContextDto $context): AccountFormViewDto
    {
        if ($me->isStaff()) {
            abort(403);
        }

        $projects = $this->getAccessibleProjects($me);

        return new AccountFormViewDto(
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

    public function getEditView(AdminUser $me, int $id, RouteContextDto $context): AccountFormViewDto
    {
        $adminUser = AdminUser::with('permissions')->findOrFail($id);

        if (!$me->canEditUser($adminUser)) {
            abort(403, 'このアカウントを編集する権限がありません。');
        }

        $projects = $this->getAccessibleProjects($me);

        return new AccountFormViewDto(
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
