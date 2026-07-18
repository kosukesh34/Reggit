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
            return $next($request);
        }

        if (! hash_equals($secret, trim((string) $request->header($header)))) {
            return response('', 401);
        }

        return $next($request);
    }
}
