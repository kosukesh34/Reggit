<?php

namespace App\UseCases\Admin\Stamp\Data;

class AdminStampGrantQrInput
{
    public function __construct(
        public readonly int $projectId,
        public readonly int $stampCardId,
        public readonly int $expiresMinutes,
        public readonly int $amount,
        public readonly string $limit,
    ) {}
}
