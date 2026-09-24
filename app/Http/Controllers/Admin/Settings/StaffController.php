<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Models\AdminUser;
use App\Models\AdminUserPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

    public function store(Request $request)
    {
        $this->authorizeManager();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'login_id' => ['required', 'string', 'max:100', Rule::unique('admin_users', 'login_id')],
            'email' => ['required', 'email', 'max:255', Rule::unique('admin_users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(AdminUserPermission::ALL_PERMISSIONS))],
        ]);
        $projectId = $this->resolveAdminProjectId();

        DB::transaction(function () use ($validated, $projectId) {
            $staff = AdminUser::create([
                'name' => $validated['name'],
                'login_id' => $validated['login_id'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => AdminUser::ROLE_STAFF,
                'project_id' => $projectId,
                'created_by_id' => auth('admin')->id(),
            ]);
            $staff->projects()->sync([$projectId]);
            $staff->syncPermissions($validated['permissions'] ?? []);
        });

        return redirect()->route('admin.settings.staff', $this->buildAdminRouteParams())
            ->with('success', 'スタッフを追加しました');
    }

    public function update(Request $request, string $projectKey, int $id)
    {
        $this->authorizeManager();
        $staff = $this->findStaff($id);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admin_users', 'email')->ignore($staff->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(array_keys(AdminUserPermission::ALL_PERMISSIONS))],
        ]);

        DB::transaction(function () use ($staff, $validated) {
            $staff->name = $validated['name'];
            $staff->email = $validated['email'];
            if (($validated['password'] ?? null) !== null) {
                $staff->password = $validated['password'];
            }
            $staff->save();
            $staff->syncPermissions($validated['permissions'] ?? []);
        });

        return redirect()->route('admin.settings.staff', $this->buildAdminRouteParams())
            ->with('success', 'スタッフの権限を更新しました');
    }

    private function authorizeManager(): void
    {
        abort_unless(auth('admin')->user()?->canCreateRole(AdminUser::ROLE_STAFF), 403);
    }

    private function findStaff(int $id): AdminUser
    {
        $staff = AdminUser::query()->where('role', AdminUser::ROLE_STAFF)->findOrFail($id);
        abort_unless($staff->canAccessProject($this->resolveAdminProjectId()), 404);

        return $staff;
    }
}
