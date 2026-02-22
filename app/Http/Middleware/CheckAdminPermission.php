<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ルートに必要な権限キーをチェックするミドルウェア。
 * 使い方: ->middleware('admin.permission:orders.view')
 * スタッフ以外のロールは全権限を持つとみなす。
 */
class CheckAdminPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $admin = $request->user('admin');
        if ($admin === null) {
            abort(403, 'ログインが必要です。');
        }

        // スタッフ以外は全権限を持つ
        if ($admin->isStaff() === false) {
            return $next($request);
        }

        // スタッフ: いずれかの権限を持っていれば許可
        foreach ($permissions as $permission) {
            if ($admin->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'この操作を行う権限がありません。');
    }
}
