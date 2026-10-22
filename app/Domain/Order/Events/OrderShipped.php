<?php

namespace App\Domain\Order\Events;

final class OrderShipped
{
    public function __construct(
        public readonly int $orderId,
        public readonly int $projectId,
        public readonly int $userId,
    ) {}
}
