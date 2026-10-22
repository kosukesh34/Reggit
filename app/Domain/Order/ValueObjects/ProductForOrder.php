<?php

namespace App\Domain\Order\ValueObjects;

final class ProductForOrder
{
    /**
     * @param  list<ProductOptionGroupSpec>  $optionGroups
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $price,
        public readonly bool $isAvailable,
        public readonly ?int $stock,
        public readonly array $optionGroups,
        public readonly bool $isTicket = false,
        public readonly bool $isSubscription = false,
    ) {}
}
