<?php

namespace App\Services\Admin;

use App\Models\AdminAuditLog;
use App\Repositories\Contracts\AdminUserRepository;
use App\Repositories\Contracts\ProjectRepository;
use App\UseCases\Admin\Auth\Data\AdminAuthAuditContext;
use App\UseCases\Admin\Auth\Data\AdminLoginInput;
use App\UseCases\Admin\Auth\Data\AdminLogoutInput;
use App\UseCases\Admin\Auth\Data\AdminRedirectResult;
use App\UseCases\Admin\Auth\Data\SystemAdminLoginInput;
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

    public function getSystemAdminDashboardRedirect(): AdminRedirectResult
    {
        return new AdminRedirectResult('system-admin.projects.index');
    }

    public function getAdminDashboardRedirect(): AdminRedirectResult
    {
        $admin = Auth::guard('admin')->user();
        if ($admin->isSystemAdmin()) {
            return $this->getSystemAdminDashboardRedirect();
        }
        $first = $this->projectRepository->findFirstActiveByIds($admin->getAllowedProjectIds());
        if ($first !== null) {
            return new AdminRedirectResult('admin.dashboard', ['project_key' => $first->project_key]);
        }

        return new AdminRedirectResult('admin.login');
    }

    /**
     * 店舗管理ログイン。
     *
     * プロジェクト指定ログイン（/admin/{project_key}/login）の場合は projectKey を指定。
     * システム管理者はプロジェクト指定時のみここからログイン可能。
     */
    public function loginAdmin(AdminLoginInput $input): AdminRedirectResult
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
            if ($project === null) {
                Log::warning('[AdminLogin] project not found', ['project_key' => $input->projectKey]);
                throw ValidationException::withMessages([
                    'login_id' => ['IDまたはパスワードが正しくありません。'],
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
            return new AdminRedirectResult('admin.dashboard', ['project_key' => $input->projectKey]);
        }

        return $this->getAdminDashboardRedirect();
    }

    public function loginSystemAdmin(SystemAdminLoginInput $input): AdminRedirectResult
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

    public function logout(AdminLogoutInput $input): AdminRedirectResult
    {
        if ($input->adminUserId !== null) {
            $this->writeLoginAuditLog($input->auditContext, $input->adminUserId, 'logout', []);
        }

        Auth::guard('admin')->logout();
        Session::invalidate();
        Session::regenerateToken();

        return $input->isSystemAdminLogout
            ? new AdminRedirectResult('system-admin.login')
            : new AdminRedirectResult('admin.login');
    }

    /**
     * @param  array<string, string|bool|null>  $payload
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
