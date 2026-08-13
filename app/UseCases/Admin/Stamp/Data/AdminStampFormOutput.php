<?php

namespace App\UseCases\Admin\Stamp\Data;

use App\Models\Coupon;
use App\Models\StampCard;
use Illuminate\Support\Collection;

/**
 * @param  Collection<int, Coupon>  $coupons
 */
class AdminStampFormOutput
{
    public function __construct(
        public readonly ?StampCard $stampCard,
        public readonly Collection $coupons,
        public readonly bool $isGachaEnabled,
    ) {}
}
