<?php

namespace App\Services\Admin;

use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\Project;
use App\UseCases\Admin\Auth\Data\AdminLoginInput;
use App\UseCases\Admin\Auth\Data\AdminLogoutInput;
use App\UseCases\Admin\Auth\Data\AdminRequestContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{
    public function getSystemAdminDashboardRedirect(): RedirectResponse
    {
        return redirect()->route('system-admin.projects.index');
    }

    public function getAdminDashboardRedirect(): RedirectResponse
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

    public function loginAdmin(AdminLoginInput $input): RedirectResponse
    {
        $projectKey = $input->projectKey !== null ? trim($input->projectKey) : null;
        $projectKey = $projectKey === '' ? null : $projectKey;

        Log::info('[AdminLogin] attempt', [
            'path' => $input->path,
            'project_key' => $projectKey,
            'login_id' => $input->loginId,
        ]);

        $user = AdminUser::where('login_id', $input->loginId)->first()
            ?? AdminUser::where('email', $input->loginId)->first();
        if ($user === null) {
            Log::warning('[AdminLogin] user not found', ['login_id' => $input->loginId]);
            throw ValidationException::withMessages([
                'login_id' => ['IDまたはパスワードが正しくありません。'],
            ]);
        }
        if (Hash::check($input->password, $user->password) === false) {
            Log::warning('[AdminLogin] password mismatch', ['login_id' => $input->loginId]);
            throw ValidationException::withMessages([
                'login_id' => ['IDまたはパスワードが正しくありません。'],
            ]);
        }
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

        Auth::guard('admin')->login($user, $input->remember);
        session()->regenerate();
        session()->put('admin_login_via', 'admin');

        $this->writeLoginAuditLog($input->context, $user->id, 'login_success', [
            'login_id' => $input->loginId,
            'remember' => $input->remember,
            'project_key' => $projectKey,
        ]);

        if ($projectKey !== null && $projectKey !== '') {
            return redirect()->route('admin.dashboard', ['project_key' => $projectKey]);
        }

        return $this->getAdminDashboardRedirect();
    }

    public function loginSystemAdmin(AdminLoginInput $input): RedirectResponse
    {
        $user = AdminUser::where('login_id', $input->loginId)->first()
            ?? AdminUser::where('email', $input->loginId)->first();
        if ($user === null || Hash::check($input->password, $user->password) === false) {
            throw ValidationException::withMessages([
                'login_id' => ['IDまたはパスワードが正しくありません。'],
            ]);
        }
        if ($user->isSystemAdmin() === false) {
            throw ValidationException::withMessages([
                'login_id' => ['システム管理者アカウントでログインしてください。'],
            ]);
        }

        Auth::guard('admin')->login($user, $input->remember);
        session()->regenerate();
        session()->put('admin_login_via', 'system-admin');

        $this->writeLoginAuditLog($input->context, $user->id, 'login_success', [
            'login_id' => $input->loginId,
            'remember' => $input->remember,
        ]);

        return $this->getSystemAdminDashboardRedirect();
    }

    public function logout(AdminLogoutInput $input): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        if ($admin !== null) {
            $this->writeLoginAuditLog($input->context, $admin->id, 'logout', []);
        }

        Auth::guard('admin')->logout();
        session()->invalidate();
        session()->regenerateToken();

        return $input->isSystemAdminLogout
            ? redirect()->route('system-admin.login')
            : redirect()->route('admin.login');
    }

    /** @param array<string, mixed> $payload */
    private function writeLoginAuditLog(AdminRequestContext $context, int $adminUserId, string $event, array $payload): void
    {
        $payload['event'] = $event;

        AdminAuditLog::create([
            'admin_user_id' => $adminUserId,
            'project_id' => null,
            'route_name' => $context->routeName,
            'method' => $context->method,
            'url' => strlen($context->fullUrl) > 500 ? substr($context->fullUrl, 0, 497).'...' : $context->fullUrl,
            'ip' => $context->ip,
            'user_agent' => substr($context->userAgent, 0, 500),
            'payload' => $payload,
        ]);
    }
}
