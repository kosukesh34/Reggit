<?php

namespace App\UseCases\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use App\UseCases\Admin\Auth\Data\AdminLoginInput;
use App\UseCases\Admin\Auth\Data\AdminRedirectResult;

class AdminLoginUseCase
{
    public function __construct(
        private AdminAuthService $authService,
    ) {}

    public function handle(AdminLoginInput $input): AdminRedirectResult
    {
        return $this->authService->loginAdmin($input);
    }
}
