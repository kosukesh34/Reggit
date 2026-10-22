<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySmaregiWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            return $next($request);
        }

        $secret = (string) config('smaregi.webhook_secret');
        $header = (string) config('smaregi.webhook_secret_header');
        if ($secret === '' || $header === '') {
            // 秘密未設定のまま通すと改ざん Webhook を受理してしまう。ローカルとテストだけ許可する
            if (! app()->environment(['local', 'testing'])) {
                return response('', Response::HTTP_UNAUTHORIZED);
            }

            return $next($request);
        }

        if (! hash_equals($secret, trim((string) $request->header($header)))) {
            return response('', Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
