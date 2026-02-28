<?php

namespace App\UseCases\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use Illuminate\Http\RedirectResponse;

class GetAdminDashboardRedirectUseCase
{
    private AdminAuthService $authService;

    public function __construct(AdminAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle(): RedirectResponse
    {
        return $this->authService->getAdminDashboardRedirect();
    }
}
