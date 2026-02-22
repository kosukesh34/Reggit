<?php

namespace App\UseCases\Coupon\Data;

/** 会員クーポン1件の表示用データ。不変。 */
class MemberCoupon
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

    /** @return array{id: int, coupon_name: string, image_url: string|null, status: string, issued_at: string|null, valid_from: string|null, valid_until: string|null, used_at: string|null} */
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
