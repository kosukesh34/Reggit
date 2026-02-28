<?php

namespace App\UseCases\Admin\Coupon\Data;

use App\Models\Coupon;
use Illuminate\Support\Collection;

/**
 * 管理画面クーポン作成/編集フォーム UseCase の出力。不変。
 * 作成時は coupon が null。
 *
 * @param Collection<int, \App\Models\StampCard> $stampCards
 */
class AdminCouponFormOutput
{
    public function __construct(
        public ?Coupon $coupon,
        public Collection $stampCards
    ) {
    }
}
