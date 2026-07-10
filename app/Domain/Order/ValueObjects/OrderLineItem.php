<?php

namespace App\Domain\Order\ValueObjects;

final class OrderLineItem
{
    public function __construct(
        public readonly int $productId,
        public readonly int $quantity,
        public readonly float $unitPrice,
        public readonly ?string $notes,
    ) {}

    public function lineTotal(): float
    {
        return $this->unitPrice * $this->quantity;
    }

    /**
     * @param  list<self>  $items
     */
    public static function sumTotal(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += $item->lineTotal();
        }

        return $total;
    }
}
