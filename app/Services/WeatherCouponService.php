<?php

namespace App\Services;

use App\Domain\Settings\Repositories\BusinessHoursSettings;
use App\Domain\Settings\ValueObjects\BusinessHours;
use App\Domain\Weather\Policies\RainyPeriodPolicy;
use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Enums\MemberCouponSource;
use App\Mail\WeatherCouponApprovalMail;
use App\Models\AdminUser;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Models\WeatherCouponRule;
use App\Models\WeatherCouponRun;
use App\Services\Weather\OpenWeatherClient;
use App\Util\StoreContext;
use App\Util\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/** 雨の予報が出た店舗の会員に、その営業日だけ使えるクーポンを配る */
class WeatherCouponService
{
    /** 営業中は1時間ごとに判定し直す（15分ごとの実行に合わせて少し短くする） */
    private const RECHECK_MINUTES = 55;

    private const APPROVAL_MINUTES = 30;

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
        WeatherCouponRule::withoutGlobalScopes()
            ->where('is_active', true)
            ->whereHas('store', fn ($store) => $store->where('is_active', true))
            ->with(['project', 'store', 'coupon'])
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

    /** @return Collection<int, WeatherCouponRun> 管理画面で選択中の店舗の最近の判定結果 */
    public function recentRuns(): Collection
    {
        return $this->visibleRuns()
            ->with(['rule.store', 'rule.coupon'])
            ->orderByDesc('business_date')
            ->orderByDesc('id')
            ->limit(30)
            ->get();
    }

    /** 承認待ちの配布を承認して、すぐに配る */
    public function approve(int $runId, CarbonImmutable $now): void
    {
        DB::transaction(function () use ($runId, $now): void {
            $run = $this->visibleRuns()->lockForUpdate()->findOrFail($runId);
            if ($run->status !== WeatherCouponRun::STATUS_AWAITING_APPROVAL) {
                throw new \DomainException('この配布はすでに処理済みです。');
            }
            if ($run->approval_expires_at === null || $run->approval_expires_at->lte($now)) {
                throw new \DomainException('承認の期限（30分）を過ぎたため、この回は配れません。');
            }
            $rule = $run->rule()->with('store')->firstOrFail();
            $date = CarbonImmutable::parse($run->business_date->toDateString());
            [, $closesAt] = $this->businessHours($rule)->windowOn($date);
            if ($now >= $closesAt->subHours($rule->cutoff_hours_before_close)) {
                throw new \DomainException('配る締め切りを過ぎています。');
            }
            $this->issue($rule, $run, $date, $now, $closesAt);
        });
    }

    /** 今日は配らない。その営業日はもう判定せず、承認のメールも送らない */
    public function decline(int $runId, CarbonImmutable $now): void
    {
        DB::transaction(function () use ($runId, $now): void {
            $run = $this->visibleRuns()->lockForUpdate()->findOrFail($runId);
            if (! in_array($run->status, [WeatherCouponRun::STATUS_AWAITING_APPROVAL, WeatherCouponRun::STATUS_EXPIRED], true)) {
                throw new \DomainException('この配布はすでに処理済みです。');
            }
            $run->update(['status' => WeatherCouponRun::STATUS_DECLINED, 'checked_at' => $now]);
        });
    }

    private function runRule(WeatherCouponRule $rule, CarbonImmutable $now): ?string
    {
        $store = $rule->store;
        if ($store->latitude === null || $store->longitude === null) {
            return null;
        }
        $hours = $this->businessHours($rule);
        $date = $hours->businessDateAt($now);
        [$opensAt, $closesAt] = $hours->windowOn($date);
        if ($now < $opensAt->subHours($rule->check_hours_before_open)
            || $now >= $closesAt->subHours($rule->cutoff_hours_before_close)
            || ! $this->storeContext->runForStore($store, fn () => $this->businessSettings->isDateAvailable($date->toDateString(), (int) $rule->project_id))) {
            return null;
        }

        $run = WeatherCouponRun::withoutGlobalScopes()->firstOrCreate(
            ['weather_coupon_rule_id' => $rule->id, 'business_date' => $date->toDateString()],
            ['project_id' => $rule->project_id, 'status' => WeatherCouponRun::STATUS_PENDING],
        );
        if ($run->status === WeatherCouponRun::STATUS_AWAITING_APPROVAL) {
            if ($run->approval_expires_at !== null && $run->approval_expires_at->gt($now)) {
                return null;
            }
            $run->update(['status' => WeatherCouponRun::STATUS_EXPIRED]);
        }
        if (in_array($run->status, WeatherCouponRun::FINAL_STATUSES, true)
            || ($run->checked_at !== null && $run->checked_at->gt($now->subMinutes(self::RECHECK_MINUTES)))) {
            return null;
        }

        try {
            return $this->judge($rule, $run, $date, $now, $opensAt, $closesAt);
        } catch (\Throwable $exception) {
            report($exception);
            $run->update(['status' => WeatherCouponRun::STATUS_FAILED, 'checked_at' => $now, 'error_message' => $exception->getMessage()]);

            return WeatherCouponRun::STATUS_FAILED;
        }
    }

    private function judge(
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
            // 対象が0人だった日は上限に数えない
            ->where('issued_count', '>', 0)
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

        $run->update(['checked_at' => $now, 'max_precipitation_probability' => $maxProbability, 'error_message' => null]);
        if ($rule->requires_approval) {
            $run->update(['status' => WeatherCouponRun::STATUS_AWAITING_APPROVAL, 'approval_expires_at' => $now->addMinutes(self::APPROVAL_MINUTES)]);
            $this->requestApproval($rule, $run);

            return WeatherCouponRun::STATUS_AWAITING_APPROVAL;
        }
        $this->issue($rule, $run, $date, $now, $closesAt);

        return WeatherCouponRun::STATUS_SENT;
    }

    private function issue(WeatherCouponRule $rule, WeatherCouponRun $run, CarbonImmutable $date, CarbonImmutable $now, CarbonImmutable $closesAt): void
    {
        $memberIds = $this->targetMemberIds($rule);
        DB::transaction(function () use ($rule, $run, $date, $now, $closesAt, $memberIds): void {
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
                'coupon_delivery_id' => $delivery->id,
                'issued_count' => $result['count'],
            ]);
        });
    }

    /** 店舗の担当者とプロジェクトの管理者のうち、クーポンを管理できる人にメールで承認を求める */
    private function requestApproval(WeatherCouponRule $rule, WeatherCouponRun $run): void
    {
        $emails = AdminUser::query()
            ->where(fn ($query) => $query
                ->whereHas('stores', fn ($stores) => $stores->whereKey($rule->store_id))
                ->orWhere(fn ($admins) => $admins
                    ->where('role', AdminUser::ROLE_PROJECT_ADMIN)
                    ->where(fn ($project) => $project->where('project_id', $rule->project_id)->orWhereHas('projects', fn ($projects) => $projects->whereKey($rule->project_id)))))
            ->whereNotNull('email')
            ->get()
            ->filter(fn (AdminUser $admin) => $admin->hasPermission('coupons.manage'))
            ->pluck('email')
            ->unique();
        foreach ($emails as $email) {
            try {
                // 宛先が他の担当者に見えないよう、1人ずつ新しいメールを作る
                Mail::to($email)->send(new WeatherCouponApprovalMail(
                    (string) $rule->store->name,
                    (string) $rule->coupon?->name,
                    (int) $run->max_precipitation_probability,
                    $run->approval_expires_at->format('n月j日 H:i'),
                    route('admin.weather-coupons.index', ['project_key' => $rule->project->project_key]),
                ));
            } catch (\Throwable $exception) {
                // 1人に届かなくても、他の担当者には送る
                report($exception);
            }
        }
    }

    private function businessHours(WeatherCouponRule $rule): BusinessHours
    {
        $range = $this->storeContext->runForStore($rule->store, fn () => $this->businessSettings->getBusinessHours((int) $rule->project_id));

        return new BusinessHours((string) $range['start'], (string) $range['end']);
    }

    /** @return Builder<WeatherCouponRun> */
    private function visibleRuns(): Builder
    {
        $storeIds = $this->storeContext->visibleStoreIds;

        return WeatherCouponRun::query()->when($storeIds !== null, fn ($query) => $query->whereHas('rule', fn ($rule) => $rule->whereIn('store_id', $storeIds)));
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
