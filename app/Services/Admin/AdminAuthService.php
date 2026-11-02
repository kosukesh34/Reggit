<?php

namespace App\Services\Admin;

use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Notifications\AdminLoginAlert;
use App\Repositories\Contracts\AdminUserRepository;
use App\Repositories\Contracts\ProjectRepository;
use App\Services\Admin\Data\AdminAuthAuditContext;
use App\Services\Admin\Data\AdminLoginInput;
use App\Services\Admin\Data\AdminLogoutInput;
use App\Services\Admin\Data\AdminRedirectResult;
use App\Services\Admin\Data\SystemAdminLoginInput;
use App\Support\AdminPasskeyGate;
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
            return $this->projectLoginRedirect($admin, $first->project_key);
        }

        Auth::guard('admin')->logout();
        Session::forget('admin_project_id');

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

        if (! $user->isSystemAdmin() && $this->projectRepository->findFirstActiveByIds($user->getAllowedProjectIds()) === null) {
            throw ValidationException::withMessages(['login_id' => ['このプロジェクトは利用停止または解約されています。運営にお問い合わせください。']]);
        }
        if ($input->projectKey !== null && ! $user->canAccessProject((int) $project->id)) {
            throw ValidationException::withMessages(['login_id' => ['このプロジェクトへのアクセス権がありません。']]);
        }

        $this->assertLoginIpAllowed($user, $input->auditContext->ip);

        Log::info('[AdminLogin] success', ['login_id' => $user->login_id, 'project_key' => $input->projectKey]);

        Auth::guard('admin')->login($user, $input->remember);
        Session::regenerate();
        Session::put('admin_login_via', 'admin');
        Session::put('password_hash_admin', Auth::guard('admin')->hashPasswordForCookie($user->getAuthPassword()));
        AdminPasskeyGate::markPasswordAuthenticated();

        // システムAdminの店舗管理ログインは監査しない（システム管理ログインのみ記録）
        if (! $user->isSystemAdmin()) {
            $this->writeLoginAuditLog($input->auditContext, $user->id, 'login_success', [
                'login_id' => $input->loginId,
                'remember' => $input->remember,
                'project_key' => $input->projectKey,
            ]);
        }
        $this->notifyLoginIfEnabled($user, $input->auditContext);

        $intended = ($input->projectKey !== null && $input->projectKey !== '')
            ? $this->projectLoginRedirect($user, $input->projectKey)
            : $this->getAdminDashboardRedirect();

        return $this->completeLoginGate($user, $intended);
    }

    private function projectLoginRedirect(AdminUser $admin, string $projectKey): AdminRedirectResult
    {
        if (! $admin->isStaff() || $admin->hasPermission('dashboard.view')) {
            return new AdminRedirectResult('admin.dashboard', ['project_key' => $projectKey]);
        }

        // 支店選択画面はすべてのスタッフが利用可能。閲覧権限がない場合もログインを完了できる。
        return new AdminRedirectResult('admin.stores.index', ['project_key' => $projectKey]);
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

        $this->assertLoginIpAllowed($user, $input->auditContext->ip);

        Auth::guard('admin')->login($user, $input->remember);
        Session::regenerate();
        Session::put('admin_login_via', 'system-admin');
        Session::put('password_hash_admin', Auth::guard('admin')->hashPasswordForCookie($user->getAuthPassword()));
        AdminPasskeyGate::markPasswordAuthenticated();

        $this->writeLoginAuditLog($input->auditContext, $user->id, 'login_success', [
            'login_id' => $input->loginId,
            'remember' => $input->remember,
        ]);
        $this->notifyLoginIfEnabled($user, $input->auditContext);

        // ログイン後のプロジェクト移動では再認証しない。ログイン時のみIP・パスキーを要求する。
        return $this->completeLoginGate($user, $this->getSystemAdminDashboardRedirect());
    }

    private function completeLoginGate(AdminUser $user, AdminRedirectResult $intended): AdminRedirectResult
    {
        // パスキーを省いたログインでも、遠隔登録のリンクから来た場合は登録画面へ進める
        AdminPasskeyGate::consumeRemoteEnrollPending($user);
        if (AdminPasskeyGate::isPasskeyVerified() && ! AdminPasskeyGate::isRemoteEnroll()) {
            return $intended;
        }
        AdminPasskeyGate::rememberIntended($intended);

        return AdminPasskeyGate::nextStep($user);
    }

    private function assertLoginIpAllowed(AdminUser $user, ?string $ip): void
    {
        if ($user->allowsLoginFromIp($ip)) {
            return;
        }

        Log::warning('[AdminLogin] ip denied', [
            'login_id' => $user->login_id,
            'ip' => $ip,
        ]);
        throw ValidationException::withMessages([
            'login_id' => ['このIPアドレスからのログインは許可されていません。'],
        ]);
    }

    private function notifyLoginIfEnabled(AdminUser $user, AdminAuthAuditContext $context): void
    {
        if ($user->login_notify_by_email !== true) {
            return;
        }
        if ($user->email === null || $user->email === '') {
            return;
        }

        $user->notify(new AdminLoginAlert(
            ip: $context->ip,
            userAgent: $context->userAgent !== '' ? $context->userAgent : '不明',
            occurredAt: now()->timezone(config('app.timezone'))->format('Y-m-d H:i:s'),
        ));
    }

    public function logout(AdminLogoutInput $input): AdminRedirectResult
    {
        $admin = Auth::guard('admin')->user();
        $isSystemAdmin = $admin instanceof AdminUser && $admin->isSystemAdmin();
        // システム管理のログアウトは残す。店舗管理側のシステムAdminログアウトは残さない。
        if ($input->adminUserId !== null && ($input->isSystemAdminLogout || ! $isSystemAdmin)) {
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
