<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\AdminUser;
use App\UseCases\Admin\Auth\GetAdminDashboardRedirectUseCase;
use App\UseCases\Admin\Auth\SystemAdminLoginUseCase;
use Illuminate\Support\Facades\Auth;

class SystemAdminLoginController extends Controller
{
    private GetAdminDashboardRedirectUseCase $getDashboardRedirectUseCase;
    private SystemAdminLoginUseCase $systemAdminLoginUseCase;

    public function __construct(
        GetAdminDashboardRedirectUseCase $getDashboardRedirectUseCase,
        SystemAdminLoginUseCase $systemAdminLoginUseCase
    ) {
        $this->getDashboardRedirectUseCase = $getDashboardRedirectUseCase;
        $this->systemAdminLoginUseCase = $systemAdminLoginUseCase;
    }

    public function show()
    {
        if (Auth::guard('admin')->check()) {
            /** @var AdminUser|null $user */
            $user = Auth::guard('admin')->user();
            if ($user !== null && $user->isSystemAdmin()) {
                return redirect()->route('system-admin.projects.index');
            }

            return $this->getDashboardRedirectUseCase->handle();
        }

        return view('admin.system-admin.login', [
            'submitRoute' => route('system-admin.login.submit'),
        ]);
    }

    public function login(LoginRequest $request)
    {
        return $this->systemAdminLoginUseCase->handle($request);
    }
}
