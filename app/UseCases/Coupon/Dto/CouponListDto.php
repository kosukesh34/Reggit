<?php

namespace App\UseCases\Coupon\Dto;

class CouponListDto
{
    /** @param array<int, MemberCouponDto> $coupons */
    public function __construct(
        public array $coupons,
        public ?string $defaultImageUrl,
        public bool $requiresMembership
    ) {
    }

    public function couponsToArray(): array
    {
        $payload = [];
        foreach ($this->coupons as $coupon) {
            $payload[] = $coupon->toArray();
        }
        return $payload;
    }
}
