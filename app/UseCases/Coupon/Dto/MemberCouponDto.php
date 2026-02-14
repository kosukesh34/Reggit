<?php

namespace App\UseCases\Coupon\Dto;

class MemberCouponDto
{
    public function __construct(
        public int $id,
        public string $couponName,
        public ?string $imageUrl,
        public string $status,
        public ?string $issuedAt,
        public ?string $validFrom,
        public ?string $validUntil,
        public ?string $usedAt
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'coupon_name' => $this->couponName,
            'image_url' => $this->imageUrl,
            'status' => $this->status,
            'issued_at' => $this->issuedAt,
            'valid_from' => $this->validFrom,
            'valid_until' => $this->validUntil,
            'used_at' => $this->usedAt,
        ];
    }
}
