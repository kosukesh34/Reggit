<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Support\AdminAuthInputFactory;
use App\Http\Support\AdminRedirect;
use App\UseCases\Admin\Auth\AdminLogoutUseCase;
use Illuminate\Http\Request;

class LogoutController extends Controller
{
    public function __construct(
        private AdminLogoutUseCase $adminLogoutUseCase,
        private AdminAuthInputFactory $inputFactory,
    ) {}

    public function __invoke(Request $request)
    {
        return AdminRedirect::toResponse($this->adminLogoutUseCase->handle(
            $this->inputFactory->logout($request),
        ));
    }
}
