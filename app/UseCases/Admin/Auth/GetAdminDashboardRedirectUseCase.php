<?php

namespace App\UseCases\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use App\UseCases\Admin\Auth\Data\AdminRedirectResult;

class GetAdminDashboardRedirectUseCase
{
    public function __construct(
        private AdminAuthService $authService,
    ) {}

    public function handle(): AdminRedirectResult
    {
        return $this->authService->getAdminDashboardRedirect();
    }
}
