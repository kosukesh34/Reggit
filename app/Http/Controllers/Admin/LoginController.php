<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LoginRequest;
use App\Models\AdminAuditLog;
use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /** 店舗管理ログイン後の最初の遷移先（先頭プロジェクトの dashboard） */
    private function getAdminDashboardRedirect(): RedirectResponse
    {
        $admin = Auth::guard('admin')->user();
        $first = Project::whereIn('id', $admin->getAllowedProjectIds())
            ->where('is_active', true)
            ->orderBy('name')
            ->first();
        if ($first !== null) {
            return redirect()->route('admin.dashboard', ['project_key' => $first->project_key]);
        }

        return redirect()->route('admin.login');
    }

    /** 店舗管理者ログイン画面（/admin/login）※システムアドミンもここからログイン可能 */
    public function showAdminLoginForm()
    {
        if (Auth::guard('admin')->check()) {
            return $this->getAdminDashboardRedirect();
        }

        return view('admin.login', [
            'submitRoute' => route('admin.login.submit'),
        ]);
    }

    /** システムアドミンのログイン画面（/system-admin/login） */
    public function showSystemAdminLoginForm()
    {
        if (Auth::guard('admin')->check()) {
            $user = Auth::guard('admin')->user();

            return $user->isSystemAdmin()
                ? redirect()->route('system-admin.projects.index')
                : $this->getAdminDashboardRedirect();
        }

        return redirect()->route('admin.login');
    }

    /** 店舗管理者ログイン画面（/store/login）※システムアドミンもここからログイン可能 */
    public function showStoreLoginForm()
    {
        if (Auth::guard('admin')->check()) {
            return $this->getAdminDashboardRedirect();
        }

        return redirect()->route('admin.login');
    }

    /** POST /admin/login - 店舗管理者・システムアドミン両方でログイン可能（店舗管理側へ） */
    public function loginAdmin(LoginRequest $request)
    {
        $user = AdminUser::where('login_id', $request->login_id)->first();
        if ($user === null || Hash::check($request->password, $user->password) === false) {
            throw ValidationException::withMessages([
                'login_id' => ['IDまたはパスワードが正しくありません。'],
            ]);
        }

        $remember = $request->boolean('remember');
        Auth::guard('admin')->login($user, $remember);
        $request->session()->regenerate();
        $request->session()->put('admin_login_via', 'admin');

        $this->writeLoginAuditLog($request, $user->id, 'login_success', [
            'login_id' => $request->login_id,
            'remember' => $remember,
        ]);

        return $this->getAdminDashboardRedirect();
    }

    /** POST /system-admin/login - システムアドミン用 */
    public function loginSystemAdmin(LoginRequest $request)
    {
        return $this->loginAdmin($request);
    }

    /** POST /store/login - 店舗管理者・システムアドミン両方でログイン可能（店舗管理側へ） */
    public function loginStore(LoginRequest $request)
    {
        return $this->loginAdmin($request);
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

    /** ログイン・ログアウトを監査ログに記録 */
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
