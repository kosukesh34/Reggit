<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Support\AdminPasskeyGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AdminPasskeyVerifyController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin instanceof AdminUser) {
            return redirect()->route('admin.login');
        }
        if (AdminPasskeyGate::isPasskeyVerified()) {
            return redirect()->intended(route('admin.root'));
        }
        if (! $admin->hasPasskeysEnabled()) {
            return redirect()->route('admin.passkey.enroll');
        }

        return view('admin.passkey.verify');
    }
}
