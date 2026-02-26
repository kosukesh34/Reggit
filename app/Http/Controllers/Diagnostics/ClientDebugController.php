<?php

namespace App\Http\Controllers\Diagnostics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClientDebugController extends Controller
{
    public function __invoke(Request $request)
    {
        $body = [];
        if ($request->isMethod('post')) {
            $contentType = (string) $request->header('Content-Type', '');
            if (str_contains(strtolower($contentType), 'application/json')) {
                $decoded = json_decode((string) $request->getContent(), true);
                if (is_array($decoded)) {
                    $body = $decoded;
                }
            } else {
                $decoded = json_decode((string) $request->getContent(), true);
                if (is_array($decoded)) {
                    $body = $decoded;
                } else {
                    $body = $request->all();
                }
            }
        }

        $event = (string) ($body['event'] ?? $request->query('event', 'unknown'));
        $projectKey = (string) ($body['project_key'] ?? $request->query('project_key', ''));
        $path = (string) ($body['path'] ?? $request->query('path', ''));
        $ua = (string) ($body['ua'] ?? $request->query('ua', ''));
        $ts = (string) ($body['ts'] ?? $request->query('ts', ''));
        $payload = (string) ($body['payload'] ?? $request->query('payload', ''));

        Log::info('[ClientDebug] ' . $event, [
            'project_key' => $projectKey,
            'path' => $path,
            'ua' => $ua,
            'ts' => $ts,
            'payload' => $payload,
            'method' => $request->method(),
        ]);

        return response()->json(['ok' => true]);
    }
}
