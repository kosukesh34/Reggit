<?php

namespace App\Http\Controllers\Admin;

use App\Services\WeatherCouponService;
use Carbon\CarbonImmutable;

class WeatherCouponController extends AdminBaseController
{
    public function __construct(
        private WeatherCouponService $weatherCoupons,
    ) {}

    public function index()
    {
        return view('admin.weather-coupons.index', ['runs' => $this->weatherCoupons->recentRuns()]);
    }

    public function approve(string $projectKey, int $id)
    {
        return $this->decide(fn () => $this->weatherCoupons->approve($id, CarbonImmutable::now()), '承認しました。雨の日クーポンを配りました。');
    }

    public function decline(string $projectKey, int $id)
    {
        return $this->decide(fn () => $this->weatherCoupons->decline($id, CarbonImmutable::now()), '今日は配らないようにしました。');
    }

    private function decide(\Closure $action, string $message)
    {
        try {
            $action();
        } catch (\DomainException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', $message);
    }
}
