<?php

namespace App\Domain\Member\Events;

final class PointsEarned
{
    public function __construct(
        public readonly int $memberId,
        public readonly int $projectId,
        public readonly int $points,
        public readonly ?int $orderId,
        public readonly bool $isBestEffortSync = false,
    ) {}
}
