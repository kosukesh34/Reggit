<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Support\AdminAuthInputFactory;
use App\Http\Support\AdminRedirect;
use App\Services\Admin\AdminSessionInspector;
use App\UseCases\Admin\Auth\GetAdminDashboardRedirectUseCase;
use App\UseCases\Admin\Auth\SystemAdminLoginUseCase;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\Response;

class SystemAdminLoginController extends Controller
{
    public function __construct(
        private GetAdminDashboardRedirectUseCase $getDashboardRedirectUseCase,
        private SystemAdminLoginUseCase $systemAdminLoginUseCase,
        private AdminAuthInputFactory $inputFactory,
        private AdminSessionInspector $adminSessionInspector,
    ) {}

    public function show(): Response|View
    {
        if ($this->adminSessionInspector->isAuthenticated()) {
            if ($this->adminSessionInspector->isSystemAdmin()) {
                return redirect()->route('system-admin.projects.index');
            }

            return AdminRedirect::toResponse($this->getDashboardRedirectUseCase->handle());
        }

        return view('admin.system-admin.login', [
            'submitRoute' => route('system-admin.login.submit'),
        ]);
    }

    public function login(LoginRequest $request)
    {
        return AdminRedirect::toResponse($this->systemAdminLoginUseCase->handle(
            $this->inputFactory->systemAdminLogin($request),
        ));
    }
}
