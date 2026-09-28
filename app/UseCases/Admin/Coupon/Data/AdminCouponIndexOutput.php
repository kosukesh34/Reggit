<?php

namespace App\UseCases\Admin\Coupon\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 管理画面クーポン一覧 UseCase の出力。
 */
class AdminCouponIndexOutput
{
    public function __construct(
        public LengthAwarePaginator $coupons,
        public LengthAwarePaginator $deliveryHistories,
    ) {}
}
