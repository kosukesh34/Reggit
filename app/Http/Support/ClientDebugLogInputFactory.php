<?php

namespace App\Http\Support;

use App\UseCases\Diagnostics\Data\ClientDebugLogInput;
use Illuminate\Http\Request;

class ClientDebugLogInputFactory
{
    public function fromRequest(Request $request): ClientDebugLogInput
    {
        $body = $this->parseBody($request);

        return new ClientDebugLogInput(
            (string) ($body['event'] ?? $request->query('event', 'unknown')),
            (string) ($body['project_key'] ?? $request->query('project_key', '')),
            (string) ($body['path'] ?? $request->query('path', '')),
            (string) ($body['ua'] ?? $request->query('ua', '')),
            (string) ($body['ts'] ?? $request->query('ts', '')),
            (string) ($body['payload'] ?? $request->query('payload', '')),
            $request->method(),
        );
    }

    /** @return array<string, mixed> */
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
