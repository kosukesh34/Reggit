<?php

namespace App\UseCases\Smaregi\Data;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmaregiWebhookInput
{
    /**
     * @param  array<string, mixed>  $body
     */
    public function __construct(
        public string $method,
        public array $body,
        public ?string $eventHeader,
        public ?string $contractIdHeader,
        public ?string $secretHeader,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $body = $request->all();
        if ($request->isMethod('POST')) {
            $raw = (string) $request->getContent();
            if ($raw !== '') {
                $decoded = json_decode($raw, true);
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
        }

        $eventHeader = $request->header('smaregi-event');
        $contractHeader = $request->header('smaregi-contract-id');
        $secretHeaderName = config('smaregi.webhook_secret_header');
        $secretHeader = is_string($secretHeaderName) && $secretHeaderName !== ''
            ? $request->header($secretHeaderName)
            : null;

        return new self(
            $request->getMethod(),
            $body,
            is_string($eventHeader) ? $eventHeader : null,
            is_string($contractHeader) ? $contractHeader : null,
            is_string($secretHeader) ? $secretHeader : null,
        );
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function isGetOrHead(): bool
    {
        $method = strtoupper($this->method);

        return $method === 'GET' || $method === 'HEAD';
    }
}
