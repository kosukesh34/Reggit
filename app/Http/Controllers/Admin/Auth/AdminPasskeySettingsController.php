<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Models\AdminUser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class AdminPasskeySettingsController extends Controller
{
    public function __invoke(): View
    {
        $admin = Auth::guard('admin')->user();
        abort_unless($admin instanceof AdminUser, 403);

        return view('admin.passkey.settings', [
            'passkeys' => $admin->passkeys()->orderByDesc('created_at')->get(),
        ]);
    }
}
