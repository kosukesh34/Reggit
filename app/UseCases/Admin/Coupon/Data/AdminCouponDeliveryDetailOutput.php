<?php

namespace App\UseCases\Admin\Coupon\Data;

class AdminCouponDeliveryDetailOutput
{
    /**
     * @param  list<array{member_id: int|null, member_name: string, member_number: string, is_issued: bool, status_label: string}>  $members
     */
    public function __construct(
        public string $couponName,
        public string $couponCode,
        public string $deliveryTypeLabel,
        public string $statusLabel,
        public string $statusBadge,
        public string $timingLabel,
        public string $issuedCountLabel,
        public string $targetSummary,
        public array $members,
        public bool $isEventRankUp,
    ) {}
}
