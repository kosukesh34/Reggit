<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStoreAdmin
{
    /**
     * システム管理者・プロジェクト管理者・店舗管理者・スタッフを許可。
     * 全ロールが店舗管理画面（/admin/*）にアクセス可能（権限はルート別ミドルウェアで制御）。
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user('admin');
        if ($admin === null || $admin->canAccessStoreAdmin() === false) {
            abort(403, 'この操作を行う権限がありません。');
        }

        return $next($request);
    }
}
