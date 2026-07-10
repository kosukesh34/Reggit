<?php

namespace App\Domain\Order\ValueObjects;

final class OrderId
{
    public function __construct(private int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('OrderId must be positive.');
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}
