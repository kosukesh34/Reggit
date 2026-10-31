<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Admin\AdminBaseController;
use App\Models\AdminUser;
use App\Models\StoreAppCredential;
use App\Util\StoreContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreAppCredentialController extends AdminBaseController
{
    public function store(Request $request, StoreContext $storeContext): RedirectResponse
    {
        $data = $request->validate(['app' => ['required', Rule::in(['kitchen', 'waiting'])]]);
        /** @var AdminUser $admin */
        $admin = $request->user('admin');
        $projectId = $this->resolveAdminProjectId();
        abort_unless(! $admin->isStaff() && $admin->canAccessProject($projectId), 403);

        $plainToken = Str::upper(Str::random(8).'-'.Str::random(8).'-'.Str::random(8));
        DB::transaction(function () use ($admin, $projectId, $data, $plainToken, $storeContext): void {
            $credential = StoreAppCredential::query()->firstOrNew([
                'project_id' => $projectId,
                'store_id' => $storeContext->resolveForWrite($projectId),
                'app' => $data['app'],
            ]);
            if ($credential->personal_access_token_id !== null) {
                DB::table('personal_access_tokens')->where('id', $credential->personal_access_token_id)->delete();
            }
            $credential->fill([
                'admin_user_id' => $admin->id,
                'personal_access_token_id' => null,
                'enrollment_token_hash' => hash('sha256', $plainToken),
                'device_key_hash' => null,
                'device_name' => null,
                'enrollment_created_at' => now(),
                'claimed_at' => null,
            ])->save();
        });

        return back()->with('store_app_enrollment', [
            'app' => $data['app'],
            'token' => $plainToken,
        ])->with('success', '端末登録コードを発行しました。旧端末の認証は失効しました。');
    }
}
