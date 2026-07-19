<?php

namespace App\UseCases\Admin\Coupon\Data;

use App\Models\Coupon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminCouponRedemptionsOutput
{
    /**
     * @param  LengthAwarePaginator<int, array{member_id: int|null, member_name: string, member_number: string, used_at: string}>  $redemptions
     */
    public function __construct(
        public Coupon $coupon,
        public LengthAwarePaginator $redemptions,
        public int $redemptionCount,
    ) {}
}
