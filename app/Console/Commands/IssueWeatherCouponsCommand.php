<?php

namespace App\Console\Commands;

use App\Models\WeatherCouponRun;
use App\Services\WeatherCouponService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class IssueWeatherCouponsCommand extends Command
{
    protected $signature = 'coupons:issue-weather';

    protected $description = '雨の予報が出た店舗の会員に雨の日クーポンを配る（15分ごとの実行を推奨）';

    public function handle(WeatherCouponService $service): int
    {
        $counts = $service->runDue(CarbonImmutable::now());
        $this->info($counts === []
            ? '判定対象のルールはありません'
            : '雨の日クーポンを判定しました: '.collect($counts)->map(fn (int $count, string $status) => "{$status}={$count}")->implode(' '));

        return isset($counts[WeatherCouponRun::STATUS_FAILED]) ? self::FAILURE : self::SUCCESS;
    }
}
