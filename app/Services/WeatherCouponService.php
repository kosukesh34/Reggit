<?php

namespace App\Services;

use App\Domain\Settings\Repositories\BusinessHoursSettings;
use App\Domain\Settings\ValueObjects\BusinessHours;
use App\Domain\Weather\Policies\RainyPeriodPolicy;
use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Enums\MemberCouponSource;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Models\WeatherCouponRule;
use App\Models\WeatherCouponRun;
use App\Services\Weather\OpenWeatherClient;
use App\Util\StoreContext;
use App\Util\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** 雨の予報が出た店舗の会員に、その営業日だけ使えるクーポンを配る */
class WeatherCouponService
{
    /** 営業中は1時間ごとに判定し直す（15分ごとの実行に合わせて少し短くする） */
    private const RECHECK_MINUTES = 55;

    public function __construct(
        private OpenWeatherClient $weather,
        private RainyPeriodPolicy $rainyPeriod,
        private MemberBenefitService $memberBenefit,
        private BusinessHoursSettings $businessSettings,
        private StoreContext $storeContext,
        private TenantContext $tenants,
    ) {}

    /** @return array<string, int> 判定結果ごとの件数 */
    public function runDue(CarbonImmutable $now): array
    {
        $counts = [];
        // ponytail: 承認制のルールは承認メールの実装（次の PR）まで配らない
        WeatherCouponRule::withoutGlobalScopes()
            ->where('is_active', true)
            ->where('requires_approval', false)
            ->with(['project', 'store'])
            ->orderBy('id')
            ->each(function (WeatherCouponRule $rule) use ($now, &$counts): void {
                try {
                    $status = $this->tenants->runForProject($rule->project, fn () => $this->runRule($rule, $now));
                } catch (\Throwable $exception) {
                    // 1つのルールの失敗で他の店舗の配布を止めない
                    report($exception);
                    $status = WeatherCouponRun::STATUS_FAILED;
                }
                if ($status !== null) {
                    $counts[$status] = ($counts[$status] ?? 0) + 1;
                }
            });

        return $counts;
    }

    private function runRule(WeatherCouponRule $rule, CarbonImmutable $now): ?string
    {
        $store = $rule->store;
        if ($store->latitude === null || $store->longitude === null) {
            return null;
        }
        $projectId = (int) $rule->project_id;
        [$hours, $date, $isOpenDay] = $this->storeContext->runForStore($store, function () use ($projectId, $now): array {
            $range = $this->businessSettings->getBusinessHours($projectId);
            $hours = new BusinessHours((string) $range['start'], (string) $range['end']);
            $date = $hours->businessDateAt($now);

            return [$hours, $date, $this->businessSettings->isDateAvailable($date->toDateString(), $projectId)];
        });
        [$opensAt, $closesAt] = $hours->windowOn($date);
        if (! $isOpenDay || $now < $opensAt->subHours($rule->check_hours_before_open) || $now >= $closesAt->subHours($rule->cutoff_hours_before_close)) {
            return null;
        }

        $run = WeatherCouponRun::withoutGlobalScopes()->firstOrCreate(
            ['weather_coupon_rule_id' => $rule->id, 'business_date' => $date->toDateString()],
            ['project_id' => $projectId, 'status' => WeatherCouponRun::STATUS_PENDING],
        );
        if (in_array($run->status, WeatherCouponRun::FINAL_STATUSES, true)
            || ($run->checked_at !== null && $run->checked_at->gt($now->subMinutes(self::RECHECK_MINUTES)))) {
            return null;
        }

        try {
            return $this->judgeAndIssue($rule, $run, $date, $now, $opensAt, $closesAt);
        } catch (\Throwable $exception) {
            report($exception);
            $run->update(['status' => WeatherCouponRun::STATUS_FAILED, 'checked_at' => $now, 'error_message' => $exception->getMessage()]);

            return WeatherCouponRun::STATUS_FAILED;
        }
    }

    private function judgeAndIssue(
        WeatherCouponRule $rule,
        WeatherCouponRun $run,
        CarbonImmutable $date,
        CarbonImmutable $now,
        CarbonImmutable $opensAt,
        CarbonImmutable $closesAt,
    ): string {
        $sentThisWeek = WeatherCouponRun::withoutGlobalScopes()
            ->where('weather_coupon_rule_id', $rule->id)
            ->where('status', WeatherCouponRun::STATUS_SENT)
            ->whereBetween('business_date', [$date->startOfWeek()->toDateString(), $date->toDateString()])
            ->count();
        if ($sentThisWeek >= $rule->weekly_limit) {
            $run->update(['status' => WeatherCouponRun::STATUS_LIMIT_REACHED, 'checked_at' => $now]);

            return WeatherCouponRun::STATUS_LIMIT_REACHED;
        }

        $from = $now->max($opensAt);
        $forecasts = $this->weather->hourly((float) $rule->store->latitude, (float) $rule->store->longitude);
        $maxProbability = collect($forecasts)
            ->filter(fn ($forecast) => $forecast->at < $closesAt && $forecast->at->addHour() > $from)
            ->max('precipitationProbability');
        if (! $this->rainyPeriod->isRainy($forecasts, $from, $closesAt, $rule->min_precipitation_probability, $rule->min_rain_hours)) {
            $run->update(['status' => WeatherCouponRun::STATUS_NO_RAIN, 'checked_at' => $now, 'max_precipitation_probability' => $maxProbability]);

            return WeatherCouponRun::STATUS_NO_RAIN;
        }

        $memberIds = $this->targetMemberIds($rule);
        DB::transaction(function () use ($rule, $run, $date, $now, $closesAt, $memberIds, $maxProbability): void {
            // 空配列は「全会員」を意味するため、対象がいないときは発行しない
            $result = $memberIds === [] ? ['count' => 0, 'member_ids' => []] : $this->memberBenefit->issueCouponToMembers(
                (int) $rule->project_id,
                (int) $rule->coupon_id,
                $memberIds,
                MemberCouponSource::WEATHER_RAIN,
                $date->toDateString(),
                true,
                // 営業日の終わりまで。日をまたぐ営業は翌日の閉店まで
                $date->endOfDay()->max($closesAt->subSecond()),
            );
            $delivery = CouponDelivery::withoutGlobalScopes()->create([
                'project_id' => $rule->project_id,
                'coupon_id' => $rule->coupon_id,
                'delivery_type' => CouponDeliveryType::EVENT_WEATHER_RAIN,
                'status' => CouponDeliveryStatus::SENT,
                'sent_at' => $now,
                'target_member_ids' => $memberIds === [] ? null : $memberIds,
                'issued_member_ids' => $result['member_ids'] === [] ? null : $result['member_ids'],
                'issued_count' => $result['count'],
                'segment_criteria' => ['weather_coupon_rule_id' => $rule->id, 'store_id' => $rule->store_id, 'business_date' => $date->toDateString()],
            ]);
            $run->update([
                'status' => WeatherCouponRun::STATUS_SENT,
                'checked_at' => $now,
                'max_precipitation_probability' => $maxProbability,
                'coupon_delivery_id' => $delivery->id,
                'issued_count' => $result['count'],
                'error_message' => null,
            ]);
        });

        return WeatherCouponRun::STATUS_SENT;
    }

    /** @return list<int> */
    private function targetMemberIds(WeatherCouponRule $rule): array
    {
        return Member::query()
            ->where('project_id', $rule->project_id)
            ->when(
                $rule->target === WeatherCouponRule::TARGET_STORE_MEMBERS,
                fn ($query) => $query->whereHas('stores', fn ($stores) => $stores->whereKey($rule->store_id)),
            )
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
