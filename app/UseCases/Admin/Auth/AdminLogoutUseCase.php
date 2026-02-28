<?php

namespace App\UseCases\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class AdminLogoutUseCase
{
    private AdminAuthService $authService;

    public function __construct(AdminAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle(Request $request): RedirectResponse
    {
        return $this->authService->logout($request);
    }
}
