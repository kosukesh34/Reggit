<?php

namespace App\UseCases\Admin\Account;

use App\Models\AdminUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AccountIndexUseCase
{
    /** @return array{adminUsers: LengthAwarePaginator} */
    public function handle(AdminUser $me): array
    {
        $query = AdminUser::with('project', 'projects');

        if ($me->isSystemAdmin()) {
            // no filter
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

        return ['adminUsers' => $adminUsers];
    }
}
