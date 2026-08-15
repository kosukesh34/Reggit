<?php

namespace App\UseCases\Admin\Coupon\Data;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * 管理画面クーポン一覧 UseCase の出力。不変。
 *
 * @param  list<array{
 *     id: int,
 *     coupon_name: string,
 *     coupon_code: string,
 *     delivery_type_label: string,
 *     status: string,
 *     status_label: string,
 *     status_badge: string,
 *     timing_label: string,
 *     issued_count_label: string,
 *     recipients_label: string,
 *     recipients_title: string
 * }>  $deliveryHistories
 */
class AdminCouponIndexOutput
{
    public function __construct(
        public LengthAwarePaginator $coupons,
        public array $deliveryHistories = [],
    ) {}
}
