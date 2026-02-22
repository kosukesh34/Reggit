<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\Project;
use App\Services\Admin\AdminAuthService;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    public function __construct(
        private AdminAuthService $authService
    ) {
    }

    /** プロジェクト選択画面（/admin/login） */
    public function show()
    {
        if (Auth::guard('admin')->check()) {
            return $this->authService->getAdminDashboardRedirect();
        }
        $projects = Project::where('is_active', true)->orderBy('name')->get();
        return view('admin.project-login-select', ['projects' => $projects]);
    }

    /** ID・パスワードのみのログイン画面（プロジェクトはログイン後に自動選択） */
    public function showLoginForm()
    {
        if (Auth::guard('admin')->check()) {
            return $this->authService->getAdminDashboardRedirect();
        }
        return view('admin.login', [
            'submitRoute' => route('admin.login.submit'),
        ]);
    }

    /** プロジェクト指定のログイン画面（/admin/{project_key}/login） */
    public function showProject(string $project_key)
    {
        if (Auth::guard('admin')->check()) {
            return $this->authService->getAdminDashboardRedirect();
        }
        return view('admin.login', [
            'submitRoute' => route('admin.project.login.submit', ['project_key' => $project_key]),
        ]);
    }

    public function login(LoginRequest $request)
    {
        return $this->authService->loginAdmin($request);
    }
}
