<?php

namespace App\UseCases\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use App\UseCases\Admin\Auth\Data\AdminLogoutInput;
use Illuminate\Http\RedirectResponse;

class AdminLogoutUseCase
{
    public function __construct(
        private AdminAuthService $authService,
    ) {}

    public function handle(AdminLogoutInput $input): RedirectResponse
    {
        return $this->authService->logout($input);
    }
}
