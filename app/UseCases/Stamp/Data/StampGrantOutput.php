<?php

namespace App\UseCases\Stamp\Data;

class StampGrantOutput
{
    public function __construct(
        public readonly bool $isSuccess,
        public readonly int $status,
        public readonly string $message,
        public readonly ?int $stampCardId = null,
        public readonly ?int $currentStamps = null,
        public readonly int $grantedCount = 0,
    ) {}
}
