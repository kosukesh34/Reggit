<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\UseCases\Admin\Auth\AdminLogoutUseCase;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    private AdminLogoutUseCase $adminLogoutUseCase;

    public function __construct(AdminLogoutUseCase $adminLogoutUseCase)
    {
        $this->adminLogoutUseCase = $adminLogoutUseCase;
    }

    public function __invoke(Request $request)
    {
        return $this->adminLogoutUseCase->handle($request);
    }
}
