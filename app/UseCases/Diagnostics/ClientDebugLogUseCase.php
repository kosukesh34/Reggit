<?php

namespace App\UseCases\Diagnostics;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * クライアントデバッグログを記録する。
 */
class ClientDebugLogUseCase
{
    /**
     * リクエストからデバッグ情報を抽出してログに記録する。
     */
    public function handle(Request $request): void
    {
        $body = $this->parseBody($request);
        $event = (string) ($body['event'] ?? $request->query('event', 'unknown'));
        $projectKey = (string) ($body['project_key'] ?? $request->query('project_key', ''));
        $path = (string) ($body['path'] ?? $request->query('path', ''));
        $ua = (string) ($body['ua'] ?? $request->query('ua', ''));
        $ts = (string) ($body['ts'] ?? $request->query('ts', ''));
        $payload = (string) ($body['payload'] ?? $request->query('payload', ''));

        Log::info('[ClientDebug] '.$event, [
            'project_key' => $projectKey,
            'path' => $path,
            'ua' => $ua,
            'ts' => $ts,
            'payload' => $payload,
            'method' => $request->method(),
        ]);
    }

    /** @return array<string, bool|int|float|string|array|object|null> */
    private function parseBody(Request $request): array
    {
        if (! $request->isMethod('post')) {
            return [];
        }
        $contentType = (string) $request->header('Content-Type', '');
        if (str_contains(strtolower($contentType), 'application/json')) {
            $decoded = json_decode((string) $request->getContent(), true);

            return is_array($decoded) ? $decoded : [];
        }
        $decoded = json_decode((string) $request->getContent(), true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return $request->all();
    }
}
