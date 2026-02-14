<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminAuthService;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __construct(
        private AdminAuthService $authService
    ) {
    }

    public function __invoke(Request $request)
    {
        return $this->authService->logout($request);
    }
}
