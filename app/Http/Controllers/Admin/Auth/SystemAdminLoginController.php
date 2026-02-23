<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Services\Admin\AdminAuthService;
use Illuminate\Support\Facades\Auth;

class SystemAdminLoginController extends Controller
{
    public function __construct(
        private AdminAuthService $authService
    ) {
    }

    public function show()
    {
        if (Auth::guard('admin')->check()) {
            $user = Auth::guard('admin')->user();
            return $user->isSystemAdmin()
                ? redirect()->route('system-admin.projects.index')
                : $this->authService->getAdminDashboardRedirect();
        }
        return view('admin.system-admin.login', [
            'submitRoute' => route('system-admin.login.submit'),
        ]);
    }

    public function login(LoginRequest $request)
    {
        return $this->authService->loginSystemAdmin($request);
    }
}
