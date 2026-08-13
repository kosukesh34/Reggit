<?php

namespace App\UseCases\Stamp\Data;

class StampGrantOpenOutput
{
    public function __construct(
        public readonly bool $isSuccess,
        public readonly int $status,
        public readonly string $message,
        public readonly ?string $redirectUrl,
    ) {}
}
