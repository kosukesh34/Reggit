<?php

namespace App\UseCases\Admin\Coupon\Data;

class AdminCouponDeliveryPreviewMembersOutput
{
    /**
     * @param  list<array{member_id: int, member_name: string, member_number: string}>  $members
     */
    public function __construct(
        public array $members,
        public int $totalCount,
        public ?string $error,
    ) {}
}
