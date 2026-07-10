<?php

namespace App\UseCases\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use App\UseCases\Admin\Auth\Data\AdminRedirectResult;
use App\UseCases\Admin\Auth\Data\SystemAdminLoginInput;

class SystemAdminLoginUseCase
{
    public function __construct(
        private AdminAuthService $authService,
    ) {}

    public function handle(SystemAdminLoginInput $input): AdminRedirectResult
    {
        return $this->authService->loginSystemAdmin($input);
    }
}
