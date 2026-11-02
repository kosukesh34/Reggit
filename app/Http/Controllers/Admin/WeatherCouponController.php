<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\WeatherCouponRuleRequest;
use App\Services\WeatherCouponService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class WeatherCouponController extends AdminBaseController
{
    public function __construct(
        private WeatherCouponService $weatherCoupons,
    ) {}

    public function index(Request $request)
    {
        $rules = $this->weatherCoupons->rules();

        return view('admin.weather-coupons.index', [
            'runs' => $this->weatherCoupons->recentRuns(),
            'rules' => $rules,
            'editing' => $rules->firstWhere('id', (int) $request->query('edit')),
            ...$this->weatherCoupons->ruleOptions(),
        ]);
    }

    public function storeRule(WeatherCouponRuleRequest $request)
    {
        $this->weatherCoupons->saveRule(null, $request->rule());

        return redirect(admin_route('admin.weather-coupons.index'))->with('success', '配布ルールを登録しました。');
    }

    public function updateRule(WeatherCouponRuleRequest $request, string $projectKey, int $id)
    {
        $this->weatherCoupons->saveRule($id, $request->rule());

        return redirect(admin_route('admin.weather-coupons.index'))->with('success', '配布ルールを更新しました。');
    }

    public function destroyRule(string $projectKey, int $id)
    {
        $this->weatherCoupons->deleteRule($id);

        return back()->with('success', '配布ルールを削除しました。');
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
