<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Notifications\AdminPasskeyRecovery;
use App\Support\AdminPasskeyAudit;
use App\Support\AdminPasskeyGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminPasskeyRecoveryController extends Controller
{
    public function send(): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin instanceof AdminUser) {
            return redirect()->route('admin.login');
        }
        if (! AdminPasskeyGate::requiresPasskeyStep() || ! $admin->hasPasskeysEnabled()) {
            return redirect()->route(AdminPasskeyGate::nextStep($admin)->route);
        }
        if ($admin->email === null || $admin->email === '') {
            return back()->withErrors(['email' => '登録メールアドレスがないため、メール復旧を使えません。管理者に連絡してください。']);
        }

        $admin->notify(new AdminPasskeyRecovery);
        AdminPasskeyAudit::record($admin, 'passkey_recovery_email_sent', [], 'admin.passkey.recovery.email');

        return back()->with('status', '登録メールアドレスに再登録用のリンクを送信しました。');
    }

    public function confirm(Request $request): RedirectResponse
    {
        if (! $request->hasValidSignature()) {
            return redirect()->route('admin.passkey.verify')
                ->withErrors(['email' => '再登録リンクが無効または期限切れです。もう一度メールを送信してください。']);
        }

        $admin = Auth::guard('admin')->user();
        if (! $admin instanceof AdminUser) {
            return redirect()->route('admin.login');
        }
        if ((int) $request->query('admin') !== (int) $admin->getAuthIdentifier()
            || ! hash_equals(sha1((string) $admin->getEmailForPasswordReset()), (string) $request->query('hash'))) {
            return redirect()->route('admin.passkey.verify')
                ->withErrors(['email' => '再登録リンクが現在のログインユーザーと一致しません。']);
        }
        if (! AdminPasskeyGate::requiresPasskeyStep()) {
            return redirect()->intended(route('admin.root'));
        }

        $admin->passkeys()->delete();
        AdminPasskeyAudit::record($admin, 'passkey_recovery_completed', [], 'admin.passkey.recovery.confirm', 'GET');

        return redirect()->route('admin.passkey.enroll')
            ->with('status', 'メール確認が完了しました。新しいパスキーを登録してください。');
    }
}
