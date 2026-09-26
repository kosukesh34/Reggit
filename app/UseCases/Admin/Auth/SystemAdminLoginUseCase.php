<?php

namespace App\UseCases\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use App\Services\Admin\Data\AdminRedirectResult;
use App\Services\Admin\Data\SystemAdminLoginInput;

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
