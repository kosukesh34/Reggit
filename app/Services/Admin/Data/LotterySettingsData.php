<?php

namespace App\Services\Admin\Data;

use App\Models\Coupon;
use App\Models\LotteryPrize;
use App\Models\Settings\ProjectLotterySetting;
use Illuminate\Support\Collection;

class LotterySettingsData
{
    /**
     * @param  Collection<int, LotteryPrize>  $prizes
     * @param  Collection<int, Coupon>  $coupons
     */
    public function __construct(
        public ProjectLotterySetting $settings,
        public Collection $prizes,
        public Collection $coupons,
        public LotteryStatsData $stats,
    ) {}
}
