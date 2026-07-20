<?php

namespace App\UseCases\Admin\Coupon\Data;

class AdminCouponDeliveryDetailOutput
{
    /**
     * @param  list<array{member_id: int|null, member_name: string, member_number: string}>  $targetMembers
     * @param  list<array{member_id: int|null, member_name: string, member_number: string}>  $issuedMembers
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
        public array $targetMembers,
        public array $issuedMembers,
        public bool $isEventRankUp,
    ) {}
}
