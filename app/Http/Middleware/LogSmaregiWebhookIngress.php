<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * スマレジ Webhook がアプリまで到達したかをログで追えるようにする。
 */
class LogSmaregiWebhookIngress
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('[Smaregi] Webhook ingress（リクエスト到達）', [
            'method' => $request->method(),
            'path' => '/'.$request->path(),
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'content_type' => (string) $request->header('Content-Type', ''),
            'smaregi_event_header' => (string) $request->header('smaregi-event', ''),
            'smaregi_contract_id_header' => (string) $request->header('smaregi-contract-id', ''),
            'body_length' => strlen((string) $request->getContent()),
        ]);

        $response = $next($request);

        Log::info('[Smaregi] Webhook ingress response', [
            'path' => '/'.$request->path(),
            'status' => $response->getStatusCode(),
        ]);

        return $response;
    }
}
