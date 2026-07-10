<?php

namespace App\Domain\Order\ValueObjects;

final class ProductOptionChoice
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $priceDelta,
        public readonly int $groupId,
    ) {}
}
