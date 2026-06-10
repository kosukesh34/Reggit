<?php

namespace App\UseCases\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use App\UseCases\Admin\Auth\Data\AdminLoginInput;
use Illuminate\Http\RedirectResponse;

class SystemAdminLoginUseCase
{
    public function __construct(
        private AdminAuthService $authService,
    ) {}

    public function handle(AdminLoginInput $input): RedirectResponse
    {
        return $this->authService->loginSystemAdmin($input);
    }
}
