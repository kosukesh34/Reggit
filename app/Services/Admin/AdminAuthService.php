<?php

namespace App\Services\Admin;

use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{
    public function getSystemAdminDashboardRedirect()
    {
        return redirect()->route('system-admin.projects.index');
    }

    public function getAdminDashboardRedirect()
    {
        $admin = Auth::guard('admin')->user();
        if ($admin->isSystemAdmin()) {
            return $this->getSystemAdminDashboardRedirect();
        }
        $first = Project::whereIn('id', $admin->getAllowedProjectIds())
            ->where('is_active', true)
            ->orderBy('name')
            ->first();
        if ($first !== null) {
            return redirect()->route('admin.dashboard', ['project_key' => $first->project_key]);
        }

        return redirect()->route('admin.login');
    }

    /**
     * 店舗管理ログイン。
     *
     * @param  string|null  $projectKey  プロジェクト指定ログイン（/admin/{project_key}/login）の場合は指定。システム管理者はこの場合のみここからログイン可能。
     */
    public function loginAdmin(Request $request, ?string $projectKey = null)
    {
        $projectKey = $projectKey !== null ? trim($projectKey) : null;
        $projectKey = $projectKey === '' ? null : $projectKey;

        Log::info('[AdminLogin] attempt', [
            'path' => $request->path(),
            'project_key' => $projectKey,
            'login_id' => $request->input('login_id'),
        ]);

        $user = AdminUser::where('login_id', $request->login_id)->first()
            ?? AdminUser::where('email', $request->login_id)->first();
        if ($user === null) {
            Log::warning('[AdminLogin] user not found', ['login_id' => $request->input('login_id')]);
            throw ValidationException::withMessages([
                'login_id' => ['IDまたはパスワードが正しくありません。'],
            ]);
        }
        if (Hash::check($request->password, $user->password) === false) {
            Log::warning('[AdminLogin] password mismatch', ['login_id' => $request->input('login_id')]);
            throw ValidationException::withMessages([
                'login_id' => ['IDまたはパスワードが正しくありません。'],
            ]);
        }
        // プロジェクト指定時: 存在・有効確認（システム管理者は全プロジェクトにログイン可）
        if ($projectKey !== null) {
            $project = Project::where('project_key', $projectKey)->where('is_active', true)->first();
            if ($project === null && $projectKey === 'default') {
                $project = Project::firstOrCreate(
                    ['project_key' => 'default'],
                    ['name' => 'デフォルト店舗', 'description' => 'デモ用プロジェクト', 'is_active' => true]
                );
            }
            if ($project === null) {
                Log::warning('[AdminLogin] project not found', ['project_key' => $projectKey]);
                throw ValidationException::withMessages([
                    'login_id' => ['指定されたプロジェクトが見つかりません。'],
                ]);
            }
        }

        Log::info('[AdminLogin] success', ['login_id' => $user->login_id, 'project_key' => $projectKey]);

        $remember = $request->boolean('remember');
        Auth::guard('admin')->login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put('admin_login_via', 'admin');

        $this->writeLoginAuditLog($request, $user->id, 'login_success', [
            'login_id' => $request->login_id,
            'remember' => $remember,
            'project_key' => $projectKey,
        ]);

        // プロジェクト指定でログインした場合はそのプロジェクトのダッシュボードへ
        if ($projectKey !== null && $projectKey !== '') {
            return redirect()->route('admin.dashboard', ['project_key' => $projectKey]);
        }

        return $this->getAdminDashboardRedirect();
    }

    public function loginSystemAdmin(Request $request)
    {
        $user = AdminUser::where('login_id', $request->login_id)->first()
            ?? AdminUser::where('email', $request->login_id)->first();
        if ($user === null || Hash::check($request->password, $user->password) === false) {
            throw ValidationException::withMessages([
                'login_id' => ['IDまたはパスワードが正しくありません。'],
            ]);
        }
        if ($user->isSystemAdmin() === false) {
            throw ValidationException::withMessages([
                'login_id' => ['システム管理者アカウントでログインしてください。'],
            ]);
        }

        $remember = $request->boolean('remember');
        Auth::guard('admin')->login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put('admin_login_via', 'system-admin');

        $this->writeLoginAuditLog($request, $user->id, 'login_success', [
            'login_id' => $request->login_id,
            'remember' => $remember,
        ]);

        return $this->getSystemAdminDashboardRedirect();
    }

    public function logout(Request $request)
    {
        $admin = $request->user('admin');
        if ($admin !== null) {
            $this->writeLoginAuditLog($request, $admin->id, 'logout', []);
        }

        $isSystemAdminLogout = $request->routeIs('system-admin.logout');
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $isSystemAdminLogout
            ? redirect()->route('system-admin.login')
            : redirect()->route('admin.login');
    }

    private function writeLoginAuditLog(Request $request, int $adminUserId, string $event, array $payload): void
    {
        $route = $request->route();
        $payload['event'] = $event;

        AdminAuditLog::create([
            'admin_user_id' => $adminUserId,
            'project_id' => null,
            'route_name' => $route ? $route->getName() : null,
            'method' => $request->getMethod(),
            'url' => strlen($request->fullUrl()) > 500 ? substr($request->fullUrl(), 0, 497).'...' : $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'payload' => $payload,
        ]);
    }
}
