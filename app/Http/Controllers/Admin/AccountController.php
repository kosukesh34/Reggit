<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AccountStoreRequest;
use App\Http\Requests\Admin\AccountUpdateRequest;
use App\Models\AdminUser;
use App\Models\AdminUserPermission;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * アカウント一覧
     * - system_admin: 全アカウント
     * - project_admin: 同プロジェクト内の admin / staff
     * - admin: 同プロジェクト内の staff
     */
    public function index(Request $request)
    {
        /** @var AdminUser $me */
        $me = $request->user('admin');

        $query = AdminUser::with('project', 'projects');

        if ($me->isSystemAdmin()) {
            // 全件表示
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

        // ルート名の判定（system-admin context or project context）
        $routePrefix = $this->getRoutePrefix($request);

        return view('admin.accounts.index', compact('adminUsers', 'me', 'routePrefix'));
    }

    public function create(Request $request)
    {
        /** @var AdminUser $me */
        $me = $request->user('admin');

        if ($me->isStaff()) {
            abort(403);
        }

        $projects = $this->getAccessibleProjects($me);
        $creatableRoles = $me->getCreatableRoles();
        $allPermissions = AdminUserPermission::ALL_PERMISSIONS;
        $permissionGroups = AdminUserPermission::PERMISSION_GROUPS;
        $routePrefix = $this->getRoutePrefix($request);

        return view('admin.accounts.create', compact(
            'projects', 'creatableRoles', 'allPermissions', 'permissionGroups', 'me', 'routePrefix'
        ));
    }

    public function store(AccountStoreRequest $request)
    {
        $admin = AdminUser::create($request->toAccountPayload());

        $projectIds = $request->toProjectIds();
        if (is_array($projectIds) && count($projectIds) > 0) {
            $admin->projects()->sync($projectIds);
        }

        // staff の場合は権限を同期
        if ($admin->isStaff()) {
            $admin->syncPermissions($request->toPermissions());
        }

        $routePrefix = $this->getRoutePrefix($request);

        return redirect()->route("{$routePrefix}.accounts.index", $this->routeParams($request))
            ->with('success', 'アカウントを作成しました');
    }

    public function edit(Request $request, int $id)
    {
        /** @var AdminUser $me */
        $me = $request->user('admin');
        $adminUser = AdminUser::with('permissions')->findOrFail($id);

        if ($me->canEditUser($adminUser) === false) {
            abort(403, 'このアカウントを編集する権限がありません。');
        }

        $projects = $this->getAccessibleProjects($me);
        $creatableRoles = $me->getCreatableRoles();
        $allPermissions = AdminUserPermission::ALL_PERMISSIONS;
        $permissionGroups = AdminUserPermission::PERMISSION_GROUPS;
        $currentPermissions = $adminUser->permissions->pluck('permission')->all();
        $routePrefix = $this->getRoutePrefix($request);

        return view('admin.accounts.edit', compact(
            'adminUser', 'projects', 'creatableRoles', 'allPermissions',
            'permissionGroups', 'currentPermissions', 'me', 'routePrefix'
        ));
    }

    public function update(AccountUpdateRequest $request, int $id)
    {
        $adminUser = AdminUser::findOrFail($id);

        if ($request->filled('password')) {
            $adminUser->password = $request->validated('password');
            $adminUser->save();
        }

        $adminUser->update($request->toAccountPayload());
        $adminUser->projects()->sync($request->toProjectIds());

        // staff の場合は権限を同期
        if ($adminUser->isStaff()) {
            $adminUser->syncPermissions($request->toPermissions());
        } else {
            // staff 以外にロール変更された場合は権限をクリア
            $adminUser->permissions()->delete();
        }

        $routePrefix = $this->getRoutePrefix($request);

        return redirect()->route("{$routePrefix}.accounts.index", $this->routeParams($request))
            ->with('success', 'アカウントを更新しました');
    }

    public function destroy(Request $request, int $id)
    {
        /** @var AdminUser $me */
        $me = $request->user('admin');
        $adminUser = AdminUser::findOrFail($id);

        if ($adminUser->id === $me->id) {
            $routePrefix = $this->getRoutePrefix($request);

            return redirect()->route("{$routePrefix}.accounts.index", $this->routeParams($request))
                ->with('error', '自分自身のアカウントは削除できません。');
        }

        if ($me->canEditUser($adminUser) === false) {
            abort(403, 'このアカウントを削除する権限がありません。');
        }

        $adminUser->permissions()->delete();
        $adminUser->delete();

        $routePrefix = $this->getRoutePrefix($request);

        return redirect()->route("{$routePrefix}.accounts.index", $this->routeParams($request))
            ->with('success', 'アカウントを削除しました');
    }

    // ── ヘルパー ──

    /** 現在のルートコンテキストを判定 */
    private function getRoutePrefix(Request $request): string
    {
        $routeName = $request->route()?->getName() ?? '';
        if (str_starts_with($routeName, 'system-admin.')) {
            return 'system-admin';
        }

        return 'admin';
    }

    /** ルートパラメータ（project_key が必要な場合） */
    private function routeParams(Request $request): array
    {
        $prefix = $this->getRoutePrefix($request);
        if ($prefix === 'admin') {
            return ['project_key' => $request->route('project_key')];
        }

        return [];
    }

    /** アクセス可能なプロジェクト一覧を取得 */
    private function getAccessibleProjects(AdminUser $me): Collection
    {
        if ($me->isSystemAdmin()) {
            return Project::where('is_active', true)->orderBy('name')->get();
        }
        $allowedIds = $me->getAllowedProjectIds();

        return Project::whereIn('id', $allowedIds)->where('is_active', true)->orderBy('name')->get();
    }
}
