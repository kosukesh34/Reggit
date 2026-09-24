<?php

namespace App\Http\Middleware;

use App\Services\Smaregi\SmaregiIdpCallbackUrl;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySmaregiIdpCallbackToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        $expected = SmaregiIdpCallbackUrl::token();
        $provided = $request->query('callback_token');
        if ($expected === null || ! is_string($provided) || ! hash_equals($expected, $provided)) {
            return response('', Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
