<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Support\AdminAuthInputFactory;
use App\UseCases\Admin\Auth\AdminLoginUseCase;
use App\UseCases\Admin\Auth\GetAdminDashboardRedirectUseCase;
use Illuminate\Support\Facades\Auth;

class StoreLoginController extends Controller
{
    public function __construct(
        private GetAdminDashboardRedirectUseCase $getDashboardRedirectUseCase,
        private AdminLoginUseCase $adminLoginUseCase,
        private AdminAuthInputFactory $inputFactory,
    ) {}

    public function show()
    {
        if (Auth::guard('admin')->check()) {
            return $this->getDashboardRedirectUseCase->handle();
        }

        return redirect()->route('admin.login');
    }

    public function login(LoginRequest $request)
    {
        return $this->adminLoginUseCase->handle(
            $this->inputFactory->adminLogin($request, null),
        );
    }
}
