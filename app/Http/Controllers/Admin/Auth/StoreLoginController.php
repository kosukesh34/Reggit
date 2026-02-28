<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\UseCases\Admin\Auth\AdminLoginUseCase;
use App\UseCases\Admin\Auth\GetAdminDashboardRedirectUseCase;
use Illuminate\Support\Facades\Auth;

class StoreLoginController extends Controller
{
    private GetAdminDashboardRedirectUseCase $getDashboardRedirectUseCase;
    private AdminLoginUseCase $adminLoginUseCase;

    public function __construct(
        GetAdminDashboardRedirectUseCase $getDashboardRedirectUseCase,
        AdminLoginUseCase $adminLoginUseCase
    ) {
        $this->getDashboardRedirectUseCase = $getDashboardRedirectUseCase;
        $this->adminLoginUseCase = $adminLoginUseCase;
    }

    public function show()
    {
        if (Auth::guard('admin')->check()) {
            return $this->getDashboardRedirectUseCase->handle();
        }

        return redirect()->route('admin.login');
    }

    public function login(LoginRequest $request)
    {
        return $this->adminLoginUseCase->handle($request);
    }
}
