<?php

namespace App\Http\Middleware;

use App\Models\AdminUser;
use App\Support\AdminPasskeyGate;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPasskeyVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! AdminPasskeyGate::requiresPasskeyStep()) {
            return $next($request);
        }

        if ($request->routeIs(
            'admin.passkey.*',
            'passkey.*',
            'admin.logout',
            'system-admin.logout',
        )) {
            return $next($request);
        }

        $admin = Auth::guard('admin')->user();
        if (! $admin instanceof AdminUser) {
            return $next($request);
        }
        // システムAdminはログイン時にバイパス済み。セッション欠落時も再要求しない。
        if ($admin->isSystemAdmin()) {
            AdminPasskeyGate::markPasskeyVerified();

            return $next($request);
        }

        return redirect()->route(AdminPasskeyGate::nextStep($admin)->route);
    }
}
