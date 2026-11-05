<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Support\AdminLoginIpAllowlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminLoginSecurityController extends Controller
{
    public function currentIp(Request $request): JsonResponse
    {
        abort_unless($request->user('admin') instanceof AdminUser, 403);

        return response()->json([
            'ip' => (string) $request->ip(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin instanceof AdminUser, 403);

        $request->validate([
            'login_allowed_ips' => ['nullable', 'array', 'max:50'],
            'login_allowed_ips.*' => ['nullable', 'string', 'max:64'],
            'login_notify_by_email' => ['nullable', 'boolean'],
        ]);

        $entries = [];
        foreach ((array) $request->input('login_allowed_ips', []) as $raw) {
            $entry = trim((string) $raw);
            if ($entry === '') {
                continue;
            }
            if (! AdminLoginIpAllowlist::isValidEntry($entry)) {
                throw ValidationException::withMessages([
                    'login_allowed_ips' => ["「{$entry}」はIPアドレスまたはCIDRとして不正です。"],
                ]);
            }
            $entries[] = $entry;
        }
        $entries = array_values(array_unique($entries));

        $admin->forceFill([
            'login_allowed_ips' => $entries === [] ? null : implode("\n", $entries),
            'login_notify_by_email' => $request->boolean('login_notify_by_email'),
        ])->save();

        return back()->with('status', 'ログインセキュリティ設定を保存しました。');
    }

    public function updateEmail(Request $request): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin instanceof AdminUser, 403);

        // メールはパスワード再設定・パスキー復旧の宛先なので、乗っ取り防止に現在のパスワードを求める。
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::unique('admin_users', 'email')->ignore($admin->id)],
            'email_current_password' => ['required', 'current_password:admin'],
        ], [
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
            'email.max' => 'メールアドレスは255文字以内で入力してください。',
            'email.unique' => 'このメールアドレスは別のアカウントで使われています。',
            'email_current_password.required' => '現在のパスワードを入力してください。',
            'email_current_password.current_password' => '現在のパスワードが正しくありません。',
        ]);

        $admin->forceFill(['email' => $data['email']])->save();

        return back()->with('status', 'メールアドレスを変更しました。');
    }
}
