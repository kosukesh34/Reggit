<?php

namespace App\Http\Support;

use App\Models\AdminUser;
use App\Models\Store;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class StaffManagementAuthorizer
{
    public function __construct(private AuthenticatedAdminResolver $authenticatedAdminResolver) {}

    public function assignableRoles(Request $request): array
    {
        return array_values(array_intersect([AdminUser::ROLE_ADMIN, AdminUser::ROLE_STAFF], $request->user('admin')->getCreatableRoles()));
    }

    public function assignableStores(Request $request, int $projectId): Builder
    {
        $admin = $this->authenticatedAdminResolver->resolve($request);
        $stores = Store::where('project_id', $projectId)->where('is_active', true);
        if (! $admin->isSystemAdmin() && ! $admin->isProjectAdmin()) {
            $stores->whereHas('admins', fn ($query) => $query->where('admin_users.id', $admin->id));
        }

        return $stores->orderBy('name');
    }

    public function canManageTarget(Request $request, AdminUser $target, int $projectId): bool
    {
        $admin = $this->authenticatedAdminResolver->resolve($request);
        if (! $admin->canEditUser($target) || (! $admin->isSystemAdmin() && array_diff($target->getAllowedProjectIds(), $admin->getAllowedProjectIds()) !== [])) {
            return false;
        }
        if (! $admin->isSystemAdmin() && ! $admin->isProjectAdmin()) {
            if (collect($target->getAllowedProjectIds())->contains(fn ($id) => (int) $id !== $projectId)) {
                return false;
            }
            $allowed = $this->assignableStores($request, $projectId)->pluck('stores.id')->all();
            if ($target->stores()->where('stores.project_id', $projectId)->whereNotIn('stores.id', $allowed)->exists()) {
                return false;
            }
            if ($target->stores()->count() === 0 && (int) $target->created_by_id !== (int) $admin->id) {
                return false;
            }
        }

        return true;
    }

    public function authorizeTarget(Request $request, AdminUser $target, int $projectId): void
    {
        abort_unless($this->canManageTarget($request, $target, $projectId), 403);
    }

    public function authorize(Request $request): void
    {
        $admin = $this->authenticatedAdminResolver->resolve($request);
        abort_unless($admin->canCreateRole(AdminUser::ROLE_STAFF), 403);
    }
}
