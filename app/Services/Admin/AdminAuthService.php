<?php

namespace App\Services\Admin;

use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\Project;
use App\Repositories\Contracts\AdminUserRepository;
use App\Repositories\Contracts\ProjectRepository;
use App\UseCases\Admin\Auth\Data\AdminAuthAuditContext;
use App\UseCases\Admin\Auth\Data\AdminLoginInput;
use App\UseCases\Admin\Auth\Data\AdminLogoutInput;
use App\UseCases\Admin\Auth\Data\SystemAdminLoginInput;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class AdminAuthService
{
    public function __construct(
        private ProjectRepository $projectRepository,
        private AdminUserRepository $adminUserRepository,
    ) {}

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
        $first = $this->projectRepository->findFirstActiveByIds($admin->getAllowedProjectIds());
        if ($first !== null) {
            return redirect()->route('admin.dashboard', ['project_key' => $first->project_key]);
        }

        return redirect()->route('admin.login');
    }

    /**
     * 店舗管理ログイン。
     *
     * プロジェクト指定ログイン（/admin/{project_key}/login）の場合は projectKey を指定。
     * システム管理者はプロジェクト指定時のみここからログイン可能。
     */
    public function loginAdmin(AdminLoginInput $input): RedirectResponse
    {
        Log::info('[AdminLogin] attempt', [
            'path' => $input->requestPath,
            'project_key' => $input->projectKey,
            'login_id' => $input->loginId,
        ]);

        $user = $this->adminUserRepository->findByLoginIdOrEmail($input->loginId);
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

        if ($input->projectKey !== null) {
            $project = $this->projectRepository->findActiveByProjectKey($input->projectKey);
            if ($project === null && $input->projectKey === 'default') {
                $project = Project::firstOrCreate(
                    ['project_key' => 'default'],
                    ['name' => 'デフォルト店舗', 'description' => 'デモ用プロジェクト', 'is_active' => true]
                );
            }
            if ($project === null) {
                Log::warning('[AdminLogin] project not found', ['project_key' => $input->projectKey]);
                throw ValidationException::withMessages([
                    'login_id' => ['指定されたプロジェクトが見つかりません。'],
                ]);
            }
        }

        Log::info('[AdminLogin] success', ['login_id' => $user->login_id, 'project_key' => $input->projectKey]);

        Auth::guard('admin')->login($user, $input->remember);
        Session::regenerate();
        Session::put('admin_login_via', 'admin');

        $this->writeLoginAuditLog($input->auditContext, $user->id, 'login_success', [
            'login_id' => $input->loginId,
            'remember' => $input->remember,
            'project_key' => $input->projectKey,
        ]);

        if ($input->projectKey !== null && $input->projectKey !== '') {
            return redirect()->route('admin.dashboard', ['project_key' => $input->projectKey]);
        }

        return $this->getAdminDashboardRedirect();
    }

    public function loginSystemAdmin(SystemAdminLoginInput $input): RedirectResponse
    {
        $user = $this->adminUserRepository->findByLoginIdOrEmail($input->loginId);
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
        Session::regenerate();
        Session::put('admin_login_via', 'system-admin');

        $this->writeLoginAuditLog($input->auditContext, $user->id, 'login_success', [
            'login_id' => $input->loginId,
            'remember' => $input->remember,
        ]);

        return $this->getSystemAdminDashboardRedirect();
    }

    public function logout(AdminLogoutInput $input): RedirectResponse
    {
        if ($input->adminUserId !== null) {
            $this->writeLoginAuditLog($input->auditContext, $input->adminUserId, 'logout', []);
        }

        Auth::guard('admin')->logout();
        Session::invalidate();
        Session::regenerateToken();

        return $input->isSystemAdminLogout
            ? redirect()->route('system-admin.login')
            : redirect()->route('admin.login');
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function writeLoginAuditLog(AdminAuthAuditContext $context, int $adminUserId, string $event, array $payload): void
    {
        $payload['event'] = $event;

        AdminAuditLog::create([
            'admin_user_id' => $adminUserId,
            'project_id' => null,
            'route_name' => $context->routeName,
            'method' => $context->method,
            'url' => $context->url,
            'ip' => $context->ip,
            'user_agent' => $context->userAgent,
            'payload' => $payload,
        ]);
    }
}
