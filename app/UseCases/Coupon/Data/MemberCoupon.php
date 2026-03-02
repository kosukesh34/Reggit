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
        public ?string $usedAt,
        public string $designTemplate = 'classic',
        public ?string $themeColor = null,
        public ?string $description = null,
        public string $useDisplay = 'none',
        public ?string $code = null
    ) {}

    /** @return array<string, mixed> */
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
            'design_template' => $this->designTemplate,
            'theme_color' => $this->themeColor,
            'description' => $this->description,
            'use_display' => $this->useDisplay,
            'code' => $this->code,
        ];
    }
}
