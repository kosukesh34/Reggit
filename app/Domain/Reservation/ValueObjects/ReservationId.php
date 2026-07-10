<?php

namespace App\Domain\Reservation\ValueObjects;

final class ReservationId
{
    public function __construct(private int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('ReservationId must be positive.');
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}
