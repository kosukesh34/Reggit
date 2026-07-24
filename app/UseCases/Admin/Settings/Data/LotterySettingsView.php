<?php

namespace App\UseCases\Admin\Settings\Data;

use App\Models\LotteryPrize;
use App\Models\Settings\ProjectLotterySetting;
use Illuminate\Support\Collection;

/** 管理画面 ガチャ・スクラッチ設定 表示用 */
class LotterySettingsView
{
    /**
     * @param  Collection<int, LotteryPrize>  $prizes
     * @param  Collection<int, \App\Models\Coupon>  $coupons
     * @param  array<string, string>  $grantTriggerOptions
     * @param  array<string, string>  $rewardTypeOptions
     */
    public function __construct(
        public ProjectLotterySetting $settings,
        public Collection $prizes,
        public Collection $coupons,
        public array $grantTriggerOptions,
        public array $rewardTypeOptions,
    ) {}
}
