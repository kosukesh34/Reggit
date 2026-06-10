<?php

namespace App\UseCases\Line\Data;

use Illuminate\Http\Request;

class LineWebhookInput
{
    public function __construct(
        public string $method,
        public string $rawBody,
        public ?string $signature,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $signature = $request->header('X-Line-Signature');

        return new self(
            $request->getMethod(),
            (string) $request->getContent(),
            is_string($signature) ? $signature : null,
        );
    }

    public function isGet(): bool
    {
        return strtoupper($this->method) === 'GET';
    }
}
