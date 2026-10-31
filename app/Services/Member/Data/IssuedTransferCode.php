<?php

namespace App\Services\Member\Data;

class IssuedTransferCode
{
    public function __construct(
        public readonly string $code,
        public readonly string $expiresAt,
        public readonly int $memberId,
    ) {}
}
