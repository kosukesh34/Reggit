<?php

namespace App\Http\Support;

use App\Services\Smaregi\Data\SmaregiNotificationInput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmaregiNotificationInputFactory
{
    public function fromRequest(Request $request): SmaregiNotificationInput
    {
        $rawBody = $request->getContent();
        $body = $request->all();

        if ($request->isMethod('POST') && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (is_array($decoded)) {
                $contentType = strtolower((string) $request->header('Content-Type', ''));
                if ($contentType !== '' && ! str_contains($contentType, 'json')) {
                    Log::info('[Smaregi] JSON ボディを raw からマージ（Content-Type が application/json 以外）', [
                        'content_type' => $request->header('Content-Type'),
                    ]);
                }
                $body = array_merge($body, $decoded);
            }
        }

        $headers = [];
        foreach ($request->headers->all() as $key => $values) {
            if (! is_array($values) || $values === []) {
                continue;
            }
            $headers[strtolower((string) $key)] = (string) $values[0];
        }

        return new SmaregiNotificationInput(
            strtoupper($request->method()),
            $headers,
            $rawBody,
            $body,
        );
    }
}
