<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Support\AdminPasskeyAudit;
use App\Support\AdminPasskeyGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class AdminPasskeyRemoteEnrollController extends Controller
{
    public function create(): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin instanceof AdminUser, 403);

        $url = URL::temporarySignedRoute(
            'admin.passkey.remote-enroll.confirm',
            now()->addMinutes(30),
            [
                'admin' => $admin->getAuthIdentifier(),
                'hash' => sha1((string) $admin->getEmailForPasswordReset()),
            ],
        );

        AdminPasskeyAudit::record($admin, 'passkey_remote_enroll_link_issued', [], 'admin.passkey.remote-enroll.create');

        return back()->with('remote_enroll_url', $url);
    }

    public function confirm(Request $request): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('admin.login')
                ->withErrors(['login_id' => '別端末用の追加リンクが無効または期限切れです。ログイン設定から発行し直してください。']);
        }

        $adminId = (int) $request->query('admin');
        $hash = (string) $request->query('hash');
        $admin = Auth::guard('admin')->user();

        if (! $admin instanceof AdminUser) {
            AdminPasskeyGate::rememberRemoteEnrollPending($adminId, $hash);

            return redirect()->route('admin.login')
                ->with('status', '別端末へのパスキー追加のため、IDとパスワードでログインしてください。');
        }

        if ((int) $admin->getAuthIdentifier() !== $adminId
            || ! hash_equals(sha1((string) $admin->getEmailForPasswordReset()), $hash)) {
            return redirect()->route('admin.passkey.settings')
                ->withErrors(['email' => '別端末用の追加リンクが現在のログインユーザーと一致しません。']);
        }

        AdminPasskeyGate::markRemoteEnroll();
        AdminPasskeyAudit::record($admin, 'passkey_remote_enroll_started', [], 'admin.passkey.remote-enroll.confirm', 'GET');

        return redirect()->route('admin.passkey.enroll')
            ->with('status', 'この端末用のパスキーを登録してください。');
    }
}
