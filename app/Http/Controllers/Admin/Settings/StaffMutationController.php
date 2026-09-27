<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Http\Support\StaffManagementAuthorizer;
use App\Http\Support\StaffPermissionCatalog;
use App\UseCases\Admin\Settings\FindStaffUseCase;
use App\UseCases\Admin\Settings\StoreStaffUseCase;
use App\UseCases\Admin\Settings\UpdateStaffUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StaffMutationController extends AdminBaseController
{
    public function __construct(
        private StoreStaffUseCase $storeUseCase,
        private UpdateStaffUseCase $updateUseCase,
        private FindStaffUseCase $findStaffUseCase,
        private StaffManagementAuthorizer $staffManagementAuthorizer,
        private StaffPermissionCatalog $staffPermissionCatalog,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $this->staffManagementAuthorizer->authorize($request);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'login_id' => ['required', 'string', 'max:100', Rule::unique('admin_users', 'login_id')],
            'email' => ['required', 'email', 'max:255', Rule::unique('admin_users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($this->staffPermissionCatalog->keys())],
        ]);
        $projectId = $this->resolveAdminProjectId();

        $this->storeUseCase->handle($projectId, (int) auth('admin')->id(), $validated);

        return $this->redirectToIndex()->with('success', 'スタッフを追加しました');
    }

    public function update(Request $request, string $projectKey, int $id): RedirectResponse
    {
        $this->staffManagementAuthorizer->authorize($request);
        $staff = $this->findStaffUseCase->handle($this->resolveAdminProjectId(), $id);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admin_users', 'email')->ignore($staff->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($this->staffPermissionCatalog->keys())],
        ]);

        $this->updateUseCase->handle($staff, $validated);

        return $this->redirectToIndex()->with('success', 'スタッフの権限を更新しました');
    }

    private function redirectToIndex(): RedirectResponse
    {
        return redirect()->route('admin.settings.staff', $this->buildAdminRouteParams());
    }
}
