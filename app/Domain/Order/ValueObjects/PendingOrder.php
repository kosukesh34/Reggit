<?php

namespace App\Domain\Order\ValueObjects;

final class PendingOrder
{
    public function __construct(
        public readonly int $projectId,
        public readonly int $userId,
        public readonly string $orderType,
        public readonly string $paymentMethod,
        public readonly float $totalAmount,
        public readonly int $pointsUsed,
        public readonly ?string $notes,
    ) {}
}
