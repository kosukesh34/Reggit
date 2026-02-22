<?php

namespace App\UseCases\Coupon\Data;

/** クーポン一覧画面用の表示データ。 @param array<int, MemberCoupon> $coupons */
class CouponListView
{
    public function __construct(
        public array $coupons,
        public ?string $defaultImageUrl,
        public bool $requiresMembership
    ) {
    }

    /** @return array<int, array{id: int, coupon_name: string, image_url: string|null, status: string, issued_at: string|null, valid_from: string|null, valid_until: string|null, used_at: string|null}> */
    public function couponsToArray(): array
    {
        $payload = [];
        foreach ($this->coupons as $coupon) {
            $payload[] = $coupon->toArray();
        }
        return $payload;
    }
}
