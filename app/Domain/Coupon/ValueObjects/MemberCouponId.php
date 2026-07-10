<?php

namespace App\Domain\Coupon\ValueObjects;

final class MemberCouponId
{
    public function __construct(private int $value)
    {
        if ($value <= 0) {
            throw new \InvalidArgumentException('MemberCouponId must be positive.');
        }
    }

    public function value(): int
    {
        return $this->value;
    }
}
