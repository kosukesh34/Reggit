<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\Project;
use App\UseCases\Admin\Auth\AdminLoginUseCase;
use App\UseCases\Admin\Auth\GetAdminDashboardRedirectUseCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminLoginController extends Controller
{
    private GetAdminDashboardRedirectUseCase $getDashboardRedirectUseCase;

    private AdminLoginUseCase $adminLoginUseCase;

    public function __construct(
        GetAdminDashboardRedirectUseCase $getDashboardRedirectUseCase,
        AdminLoginUseCase $adminLoginUseCase
    ) {
        $this->getDashboardRedirectUseCase = $getDashboardRedirectUseCase;
        $this->adminLoginUseCase = $adminLoginUseCase;
    }

    /** プロジェクト選択画面（/admin/login） */
    public function show()
    {
        if (Auth::guard('admin')->check()) {
            return $this->getDashboardRedirectUseCase->handle();
        }
        $projects = Project::where('is_active', true)->orderBy('name')->get();

        return view('admin.project-login-select', ['projects' => $projects]);
    }

    /** ID・パスワードのみのログイン画面（プロジェクトはログイン後に自動選択） */
    public function showLoginForm()
    {
        if (Auth::guard('admin')->check()) {
            return $this->getDashboardRedirectUseCase->handle();
        }

        return view('admin.login', [
            'submitRoute' => route('admin.login.submit'),
        ]);
    }

    /** プロジェクト指定のログイン画面（/admin/{project_key}/login） */
    public function showProject(string $project_key)
    {
        if (Auth::guard('admin')->check()) {
            return $this->getDashboardRedirectUseCase->handle();
        }

        return view('admin.login', [
            'submitRoute' => route('admin.project.login.submit', ['project_key' => $project_key]),
        ]);
    }

    /** POST /admin/login（プロジェクト未指定）または POST /admin/{project_key}/login（プロジェクト指定） */
    public function login(LoginRequest $request)
    {
        $projectKey = $request->route('project_key');
        if ($projectKey === null && preg_match('#^admin/([^/]+)/login$#', $request->path(), $m)) {
            $projectKey = $m[1];
        }
        Log::info('[AdminLogin] controller', [
            'path' => $request->path(),
            'route_project_key' => $request->route('project_key'),
            'resolved_project_key' => $projectKey,
        ]);

        return $this->adminLoginUseCase->handle($request, $projectKey);
    }
}
