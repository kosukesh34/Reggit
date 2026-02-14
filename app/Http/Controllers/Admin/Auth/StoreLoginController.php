<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Services\Admin\AdminAuthService;
use Illuminate\Support\Facades\Auth;

class StoreLoginController extends Controller
{
    public function __construct(
        private AdminAuthService $authService
    ) {
    }

    public function show()
    {
        if (Auth::guard('admin')->check()) {
            return $this->authService->getAdminDashboardRedirect();
        }
        return redirect()->route('admin.login');
    }

    public function login(LoginRequest $request)
    {
        return $this->authService->loginAdmin($request);
    }
}
