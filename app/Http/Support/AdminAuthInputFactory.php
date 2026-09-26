<?php

namespace App\Http\Support;

use App\Http\Requests\Admin\LoginRequest;
use App\Services\Admin\Data\AdminAuthAuditContext;
use App\Services\Admin\Data\AdminLoginInput;
use App\Services\Admin\Data\AdminLogoutInput;
use App\Services\Admin\Data\SystemAdminLoginInput;
use Illuminate\Http\Request;

class AdminAuthInputFactory
{
    public function adminLogin(LoginRequest $request, ?string $projectKey): AdminLoginInput
    {
        $normalizedProjectKey = $projectKey !== null ? trim($projectKey) : null;
        if ($normalizedProjectKey === '') {
            $normalizedProjectKey = null;
        }

        return new AdminLoginInput(
            loginId: (string) $request->input('login_id'),
            password: (string) $request->input('password'),
            remember: $request->boolean('remember'),
            projectKey: $normalizedProjectKey,
            requestPath: $request->path(),
            auditContext: $this->auditContext($request),
        );
    }

    public function systemAdminLogin(LoginRequest $request): SystemAdminLoginInput
    {
        return new SystemAdminLoginInput(
            loginId: (string) $request->input('login_id'),
            password: (string) $request->input('password'),
            remember: $request->boolean('remember'),
            auditContext: $this->auditContext($request),
        );
    }

    public function logout(Request $request): AdminLogoutInput
    {
        $admin = $request->user('admin');

        return new AdminLogoutInput(
            adminUserId: $admin !== null ? (int) $admin->id : null,
            isSystemAdminLogout: $request->routeIs('system-admin.logout'),
            auditContext: $this->auditContext($request),
        );
    }

    private function auditContext(Request $request): AdminAuthAuditContext
    {
        $route = $request->route();
        $url = $request->fullUrl();
        if (strlen($url) > 500) {
            $url = substr($url, 0, 497).'...';
        }

        return new AdminAuthAuditContext(
            routeName: $route !== null ? $route->getName() : null,
            method: $request->getMethod(),
            url: $url,
            ip: $request->ip(),
            userAgent: substr((string) $request->userAgent(), 0, 500),
        );
    }
}
