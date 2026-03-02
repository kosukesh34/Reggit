<?php

namespace App\UseCases\Admin\Auth;

use App\Services\Admin\AdminAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminLoginUseCase
{
    private AdminAuthService $authService;

    public function __construct(AdminAuthService $authService)
    {
        $this->authService = $authService;
    }

    public function handle(Request $request, ?string $projectKey = null): RedirectResponse
    {
        return $this->authService->loginAdmin($request, $projectKey);
    }
}
