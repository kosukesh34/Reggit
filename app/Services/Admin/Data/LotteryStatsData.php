<?php

namespace App\Services\Admin\Data;

/** 管理画面 ガチャ・スクラッチ実績サマリー */
class LotteryStatsData
{
    /**
     * @param  list<array{
     *     reward_type: string,
     *     reward_type_label: string,
     *     granted: int,
     *     pending: int,
     *     played: int,
     *     win: int,
     *     lose: int
     * }>  $byRewardType
     * @param  list<array{
     *     coupon_id: int|null,
     *     coupon_name: string,
     *     is_lose: bool,
     *     reward_type: string,
     *     reward_type_label: string,
     *     count: int
     * }>  $winByCoupon
     */
    public function __construct(
        public int $grantedTotal,
        public int $pendingCount,
        public int $playedCount,
        public int $winCount,
        public int $loseCount,
        public int $todayPlayedCount,
        public int $todayWinCount,
        public array $byRewardType,
        public array $winByCoupon,
    ) {}
}
