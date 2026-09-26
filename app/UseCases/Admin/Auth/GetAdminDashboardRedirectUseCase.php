<?php

namespace App\UseCases\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use App\Services\Admin\Data\AdminRedirectResult;

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
