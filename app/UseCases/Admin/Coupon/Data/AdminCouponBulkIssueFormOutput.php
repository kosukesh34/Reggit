<?php

namespace App\UseCases\Admin\Coupon\Data;

use Illuminate\Support\Collection;

/**
 * 管理画面クーポン一括配布フォーム UseCase の出力。不変。
 *
 * @param Collection<int, \App\Models\Coupon> $coupons
 */
class AdminCouponBulkIssueFormOutput
{
    public function __construct(
        public Collection $coupons,
        public int $memberCount
    ) {
    }
}
