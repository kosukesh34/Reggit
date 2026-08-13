<?php

namespace App\UseCases\Admin\Stamp\Data;

use App\Models\Coupon;
use App\Models\StampCard;
use Illuminate\Support\Collection;

/**
 * @param  Collection<int, Coupon>  $coupons
 * @param  Collection<int, StampCard>  $cycleTargetCards
 */
class AdminStampFormOutput
{
    public function __construct(
        public readonly ?StampCard $stampCard,
        public readonly Collection $coupons,
        public readonly bool $isGachaEnabled,
        public readonly Collection $cycleTargetCards,
    ) {}
}
