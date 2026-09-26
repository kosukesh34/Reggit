<?php

namespace App\UseCases\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use App\Services\Admin\Data\AdminLoginInput;
use App\Services\Admin\Data\AdminRedirectResult;

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
