<?php

namespace App\UseCases\Line\Data;

class LineWebhookInput
{
    public function __construct(
        public readonly bool $isGet,
        public readonly ?string $signature,
        public readonly string $body,
    ) {}
}
