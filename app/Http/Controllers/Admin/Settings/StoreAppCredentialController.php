<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Models\AdminUser;
use App\UseCases\Admin\Settings\IssueStoreAppEnrollmentUseCase;
use App\Util\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StoreAppCredentialController extends AdminBaseController
{
    public function store(Request $request, StoreContext $storeContext, IssueStoreAppEnrollmentUseCase $enrollments): RedirectResponse
    {
        $data = $request->validate(['app' => ['required', Rule::in(['kitchen', 'waiting'])]]);
        /** @var AdminUser $admin */
        $admin = $request->user('admin');
        $projectId = $this->resolveAdminProjectId();
        abort_unless(! $admin->isStaff() && $admin->canAccessProject($projectId), 403);

        $plainToken = $enrollments->handle($admin, $projectId, (int) $storeContext->resolveForWrite($projectId), $data['app']);

        return back()->with('store_app_enrollment', [
            'app' => $data['app'],
            'token' => $plainToken,
        ])->with('success', '端末登録コードを発行しました。旧端末の認証は失効しました。');
    }
}
