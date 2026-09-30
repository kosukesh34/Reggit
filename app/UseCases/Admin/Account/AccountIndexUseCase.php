<?php

namespace App\UseCases\Admin\Account;

use App\Models\AdminUser;
use App\UseCases\Admin\Account\Data\AccountIndexView;
use App\UseCases\Admin\Data\RouteContext;

class AccountIndexUseCase
{
    public function handle(AdminUser $me, RouteContext $context): AccountIndexView
    {
        $query = AdminUser::with('project', 'projects');

        if ($me->isSystemAdmin()) {
            $query->where('role', AdminUser::ROLE_PROJECT_ADMIN);
        } elseif ($me->isProjectAdmin()) {
            $projectIds = $me->getAllowedProjectIds();
            $query->whereIn('project_id', $projectIds)
                ->whereIn('role', [AdminUser::ROLE_ADMIN, AdminUser::ROLE_STAFF]);
        } elseif ($me->isAdmin()) {
            $projectIds = $me->getAllowedProjectIds();
            $query->whereIn('project_id', $projectIds)
                ->where('role', AdminUser::ROLE_STAFF);
        } else {
            abort(403);
        }

        $adminUsers = $query->orderBy('role')->orderBy('login_id')->paginate(20);

        return new AccountIndexView($adminUsers, $me, $context->routePrefix);
    }
}
