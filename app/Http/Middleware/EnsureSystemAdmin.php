<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSystemAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');
        if (!$admin || !$admin->isSystemAdmin()) {
            abort(403, 'この操作を行う権限がありません。');
        }
        return $next($request);
    }
}
