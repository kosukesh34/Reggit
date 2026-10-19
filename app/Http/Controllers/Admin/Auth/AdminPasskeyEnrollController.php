<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use App\Support\AdminPasskeyGate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AdminPasskeyEnrollController extends Controller
{
    public function __invoke(): View|RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        if (! $admin instanceof AdminUser) {
            return redirect()->route('admin.login');
        }
        if (AdminPasskeyGate::isPasskeyVerified() && ! AdminPasskeyGate::isRemoteEnroll()) {
            return redirect()->intended(route('admin.root'));
        }
        if ($admin->hasPasskeysEnabled() && ! AdminPasskeyGate::isRemoteEnroll()) {
            return redirect()->route('admin.passkey.verify');
        }

        return view('admin.passkey.enroll');
    }
}
