<?php

namespace App\UseCases\Coupon\Data;

/** 会員クーポン使用 API の出力。不変。 */
class CouponUseMemberCouponOutput
{
    public function __construct(
        public int $id,
        public string $couponName,
        public string $status,
        public string $usedAt,
        public string $useDisplay,
        public ?string $code
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'coupon_name' => $this->couponName,
            'status' => $this->status,
            'used_at' => $this->usedAt,
            'use_display' => $this->useDisplay,
            'code' => $this->code,
        ];
    }
}
