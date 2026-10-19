<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AdminPasswordController extends Controller
{
    public function forgot()
    {
        return view('admin.password', ['mode' => 'forgot']);
    }

    public function send(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email']]);
        Password::broker('admins')->sendResetLink($data);

        return back()->with('status', '登録済みのメールアドレスの場合、パスワード再設定リンクを送信しました。');
    }

    public function reset(Request $request, string $token)
    {
        return view('admin.password', ['mode' => 'reset', 'token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $status = Password::broker('admins')->reset($data, function (AdminUser $admin, string $password) {
            $admin->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($admin));
        });
        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => '再設定リンクが無効または期限切れです。もう一度リンクを取得してください。']);
        }

        return redirect()->route('admin.login')->with('status', 'パスワードを変更しました。');
    }

    public function edit()
    {
        return redirect()->route('admin.passkey.settings');
    }

    public function change(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password:admin'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $request->user('admin')->forceFill(['password' => $data['password'], 'remember_token' => Str::random(60)])->save();
        $request->session()->regenerate();

        return back()->with('status', 'パスワードを変更しました。');
    }
}
