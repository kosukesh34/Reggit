<?php

namespace App\UseCases\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use App\UseCases\Admin\Auth\Data\SystemAdminLoginInput;
use Illuminate\Http\RedirectResponse;

class SystemAdminLoginUseCase
{
    public function __construct(
        private AdminAuthService $authService,
    ) {}

    public function handle(SystemAdminLoginInput $input): RedirectResponse
    {
        return $this->authService->loginSystemAdmin($input);
    }
}
