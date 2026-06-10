<?php

namespace App\UseCases\Diagnostics\Data;

use Illuminate\Http\Request;

class ClientDebugLogInput
{
    public function __construct(
        public string $method,
        public string $event,
        public string $projectKey,
        public string $path,
        public string $userAgent,
        public string $timestamp,
        public string $payload,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $body = [];
        if ($request->isMethod('post')) {
            $contentType = strtolower((string) $request->header('Content-Type', ''));
            if (str_contains($contentType, 'application/json')) {
                $decoded = json_decode((string) $request->getContent(), true);
                $body = is_array($decoded) ? $decoded : [];
            } else {
                $decoded = json_decode((string) $request->getContent(), true);
                $body = is_array($decoded) ? $decoded : $request->all();
            }
        }

        return new self(
            $request->getMethod(),
            (string) ($body['event'] ?? $request->query('event', 'unknown')),
            (string) ($body['project_key'] ?? $request->query('project_key', '')),
            (string) ($body['path'] ?? $request->query('path', '')),
            (string) ($body['ua'] ?? $request->query('ua', '')),
            (string) ($body['ts'] ?? $request->query('ts', '')),
            (string) ($body['payload'] ?? $request->query('payload', '')),
        );
    }
}
