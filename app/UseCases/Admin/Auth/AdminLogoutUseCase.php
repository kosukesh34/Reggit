<?php

namespace App\UseCases\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use App\Services\Admin\Data\AdminLogoutInput;
use App\Services\Admin\Data\AdminRedirectResult;

class AdminLogoutUseCase
{
    public function __construct(
        private AdminAuthService $authService,
    ) {}

    public function handle(AdminLogoutInput $input): AdminRedirectResult
    {
        return $this->authService->logout($input);
    }
}
