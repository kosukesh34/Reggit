<?php

namespace App\UseCases\Member\Data;

class IssuedTransferCode
{
    public function __construct(
        public readonly string $code,
        public readonly string $expiresAt,
        public readonly int $memberId,
    ) {}
}
