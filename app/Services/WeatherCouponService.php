<?php

namespace App\Services;

use App\Domain\Settings\Repositories\BusinessHoursSettings;
use App\Domain\Settings\ValueObjects\BusinessHours;
use App\Domain\Weather\Policies\RainyPeriodPolicy;
use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Enums\MemberCouponSource;
use App\Enums\ScheduledDeliveryType;
use App\Jobs\SendAnnouncementNotificationJob;
use App\Mail\WeatherCouponApprovalMail;
use App\Models\AdminUser;
use App\Models\AfterRegistrationDelivery;
use App\Models\Announcement;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Models\Store;
use App\Models\WeatherCouponRun;
use App\Services\Weather\OpenWeatherClient;
use App\Util\AdminContext;
use App\Util\StoreContext;
use App\Util\TenantContext;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

/**
 * スケジュール配信の「雨の日」。雨の予報が出た店舗の会員に、その営業日だけ使えるクーポンやお知らせを配る。
 * 店舗・閾値・配る相手・承認制は配信の params に持つ。
 */
class WeatherCouponService
{
    /** 営業中は1時間ごとに判定し直す（15分ごとの実行に合わせて少し短くする） */
    private const RECHECK_MINUTES = 55;

    private const APPROVAL_MINUTES = 30;

    public function __construct(
        private OpenWeatherClient $weather,
        private RainyPeriodPolicy $rainyPeriod,
        private MemberBenefitService $memberBenefit,
        private ScheduledDeliveryTargetService $targets,
        private BusinessHoursSettings $businessSettings,
        private StoreContext $storeContext,
        private TenantContext $tenants,
    ) {}

    /** @return array<string, int> 判定結果ごとの件数 */
    public function runDue(CarbonImmutable $now): array
    {
        $counts = [];
        AfterRegistrationDelivery::withoutGlobalScopes()
            ->where('schedule_type', ScheduledDeliveryType::WEATHER_RAIN)
            ->where('is_active', true)
            ->with(['project', 'coupon'])
            ->orderBy('id')
            ->each(function (AfterRegistrationDelivery $delivery) use ($now, &$counts): void {
                try {
                    $status = $this->tenants->runForProject($delivery->project, fn () => $this->runDelivery($delivery, $now));
                } catch (\Throwable $exception) {
                    // 1つの配信の失敗で他の店舗の配布を止めない
                    report($exception);
                    $status = WeatherCouponRun::STATUS_FAILED;
                }
                if ($status !== null) {
                    $counts[$status] = ($counts[$status] ?? 0) + 1;
                }
            });

        return $counts;
    }

    /**
     * 雨の日の設定を保存してよいか確かめる。店舗は選択中の店舗のうち利用中のもの、閾値は店舗の営業時間に収まること。
     *
     * @param  array<string, mixed>  $params
     */
    public function assertSettings(int $projectId, array $params): void
    {
        $store = $this->selectableStores($projectId)->firstWhere('id', (int) ($params['store_id'] ?? 0))
            ?? throw ValidationException::withMessages(['store_id' => '選択できない店舗です。']);
        [$opensAt, $closesAt] = $this->businessHours($store, $projectId)->windowOn(CarbonImmutable::today());
        $openHours = (int) $opensAt->diffInHours($closesAt);
        $errors = array_filter([
            'min_rain_hours' => (int) ($params['min_rain_hours'] ?? 0) > $openHours ? "営業時間（{$openHours}時間）以内で入力してください。" : null,
            'cutoff_hours_before_close' => (int) ($params['cutoff_hours_before_close'] ?? 0) >= $openHours ? "営業時間（{$openHours}時間）より短くしてください。" : null,
        ]);
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /** @return Collection<int, Store> 雨の日の設定で選べる店舗（選択中の店舗のうち利用中のもの） */
    public function selectableStores(int $projectId): Collection
    {
        return Store::query()
            ->where('project_id', $projectId)
            ->where('is_active', true)
            ->when($this->storeContext->visibleStoreIds !== null, fn ($query) => $query->whereIn('id', $this->storeContext->visibleStoreIds))
            ->orderBy('id')
            ->get();
    }

    /** @return Collection<int, WeatherCouponRun> 管理画面で選択中の店舗の最近の判定結果 */
    public function recentRuns(): Collection
    {
        $runs = $this->visibleRuns()
            ->with('delivery.coupon')
            ->orderByDesc('business_date')
            ->orderByDesc('id')
            ->limit(30)
            ->get();
        $stores = Store::query()->whereIn('id', $runs->map(fn (WeatherCouponRun $run) => $run->delivery?->intParam('store_id', 0))->unique()->all())->get()->keyBy('id');
        $runs->each(fn (WeatherCouponRun $run) => $run->delivery?->setRelation('weatherStore', $stores->get($run->delivery->intParam('store_id', 0))));

        return $runs;
    }

    /**
     * 雨の日の配信を送った営業日のうちに登録した会員にも送る。
     * クーポンは発行キーが同じなので、すでに受け取っていれば重ねて配らない。
     */
    public function issueForNewMember(Member $member, CarbonImmutable $now): void
    {
        WeatherCouponRun::withoutGlobalScopes()
            ->where('project_id', $member->project_id)
            ->where('status', WeatherCouponRun::STATUS_SENT)
            // 日をまたぐ営業では前日が営業日になる
            ->whereBetween('business_date', [$now->subDay()->toDateString(), $now->toDateString()])
            ->whereHas('delivery', fn ($delivery) => $delivery->where('is_active', true))
            ->with('delivery.coupon')
            ->get()
            ->each(function (WeatherCouponRun $run) use ($member, $now): void {
                $delivery = $run->delivery;
                $store = Store::query()->find($delivery->intParam('store_id', 0));
                if ($store === null || ! $this->canSend($delivery)
                    || ! in_array((int) $member->id, $this->targets->memberIds($delivery, Carbon::instance($now)), true)) {
                    return;
                }
                $hours = $this->businessHours($store, (int) $delivery->project_id);
                $date = $hours->businessDateAt($now);
                if (! $date->isSameDay($run->business_date)) {
                    return;
                }
                [, $closesAt] = $hours->windowOn($date);
                $this->send($delivery, $run, [(int) $member->id], $date, $now, $closesAt, append: true);
            });
    }

    /** 承認待ちの配信を承認して、すぐに送る */
    public function approve(int $runId, CarbonImmutable $now): void
    {
        DB::transaction(function () use ($runId, $now): void {
            $run = $this->visibleRuns()->lockForUpdate()->findOrFail($runId);
            if ($run->status !== WeatherCouponRun::STATUS_AWAITING_APPROVAL) {
                throw new \DomainException('この配信はすでに処理済みです。');
            }
            if ($run->approval_expires_at === null || $run->approval_expires_at->lte($now)) {
                throw new \DomainException('承認の期限（30分）を過ぎたため、この回は配れません。');
            }
            $delivery = $run->delivery()->with('coupon')->firstOrFail();
            if (! $delivery->is_active || ! $this->canSend($delivery)) {
                throw new \DomainException('この配信は停止中か、クーポンが停止中のため配れません。');
            }
            $store = Store::query()->findOrFail($delivery->intParam('store_id', 0));
            $date = CarbonImmutable::parse($run->business_date->toDateString());
            [, $closesAt] = $this->businessHours($store, (int) $delivery->project_id)->windowOn($date);
            if ($now >= $closesAt->subHours($delivery->intParam('cutoff_hours_before_close', 0))) {
                throw new \DomainException('配る締め切りを過ぎています。');
            }
            $this->send($delivery, $run, $this->targets->memberIds($delivery, Carbon::instance($now)), $date, $now, $closesAt);
        });
    }

    /** 今日は配らない。その営業日はもう判定せず、承認のメールも送らない */
    public function decline(int $runId, CarbonImmutable $now): void
    {
        DB::transaction(function () use ($runId, $now): void {
            $run = $this->visibleRuns()->lockForUpdate()->findOrFail($runId);
            if (! in_array($run->status, [WeatherCouponRun::STATUS_AWAITING_APPROVAL, WeatherCouponRun::STATUS_EXPIRED], true)) {
                throw new \DomainException('この配信はすでに処理済みです。');
            }
            $run->update(['status' => WeatherCouponRun::STATUS_DECLINED, 'checked_at' => $now]);
        });
    }

    private function runDelivery(AfterRegistrationDelivery $delivery, CarbonImmutable $now): ?string
    {
        $store = Store::query()->find($delivery->intParam('store_id', 0));
        if ($store === null || ! $store->is_active || $store->latitude === null || $store->longitude === null || ! $this->canSend($delivery)) {
            return null;
        }
        $projectId = (int) $delivery->project_id;
        $hours = $this->businessHours($store, $projectId);
        $date = $hours->businessDateAt($now);
        [$opensAt, $closesAt] = $hours->windowOn($date);
        if ($now < $opensAt->subHours($delivery->intParam('check_hours_before_open', 0))
            || $now >= $closesAt->subHours($delivery->intParam('cutoff_hours_before_close', 0))
            || ! $this->storeContext->runForStore($store, fn () => $this->businessSettings->isDateAvailable($date->toDateString(), $projectId))) {
            return null;
        }

        $run = WeatherCouponRun::withoutGlobalScopes()->firstOrCreate(
            ['after_registration_delivery_id' => $delivery->id, 'business_date' => $date->toDateString()],
            ['project_id' => $projectId, 'status' => WeatherCouponRun::STATUS_PENDING],
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
            return $this->judge($delivery, $store, $run, $date, $now, $opensAt, $closesAt);
        } catch (\Throwable $exception) {
            report($exception);
            $run->update(['status' => WeatherCouponRun::STATUS_FAILED, 'checked_at' => $now, 'error_message' => $exception->getMessage()]);

            return WeatherCouponRun::STATUS_FAILED;
        }
    }

    private function judge(
        AfterRegistrationDelivery $delivery,
        Store $store,
        WeatherCouponRun $run,
        CarbonImmutable $date,
        CarbonImmutable $now,
        CarbonImmutable $opensAt,
        CarbonImmutable $closesAt,
    ): string {
        $sentThisWeek = WeatherCouponRun::withoutGlobalScopes()
            ->where('after_registration_delivery_id', $delivery->id)
            ->where('status', WeatherCouponRun::STATUS_SENT)
            // 対象が0人だった日は上限に数えない
            ->where('issued_count', '>', 0)
            ->whereBetween('business_date', [$date->startOfWeek()->toDateString(), $date->toDateString()])
            ->count();
        if ($sentThisWeek >= $delivery->intParam('weekly_limit', 1)) {
            $run->update(['status' => WeatherCouponRun::STATUS_LIMIT_REACHED, 'checked_at' => $now]);

            return WeatherCouponRun::STATUS_LIMIT_REACHED;
        }

        $from = $now->max($opensAt);
        $forecasts = $this->weather->hourly((float) $store->latitude, (float) $store->longitude);
        $maxProbability = collect($forecasts)
            ->filter(fn ($forecast) => $forecast->at < $closesAt && $forecast->at->addHour() > $from)
            ->max('precipitationProbability');
        $rainy = $this->rainyPeriod->isRainy(
            $forecasts,
            $from,
            $closesAt,
            $delivery->intParam('min_precipitation_probability', 100),
            $delivery->intParam('min_rain_hours', 24),
        );
        if (! $rainy) {
            $run->update(['status' => WeatherCouponRun::STATUS_NO_RAIN, 'checked_at' => $now, 'max_precipitation_probability' => $maxProbability]);

            return WeatherCouponRun::STATUS_NO_RAIN;
        }

        $run->update(['checked_at' => $now, 'max_precipitation_probability' => $maxProbability, 'error_message' => null]);
        if ((bool) $delivery->param('requires_approval', 1)) {
            $run->update(['status' => WeatherCouponRun::STATUS_AWAITING_APPROVAL, 'approval_expires_at' => $now->addMinutes(self::APPROVAL_MINUTES)]);
            $this->requestApproval($delivery, $store, $run);

            return WeatherCouponRun::STATUS_AWAITING_APPROVAL;
        }
        $this->send($delivery, $run, $this->targets->memberIds($delivery, Carbon::instance($now)), $date, $now, $closesAt);

        return WeatherCouponRun::STATUS_SENT;
    }

    /**
     * クーポンを営業日の終わりまでの期限で配り、お知らせなら1件にまとめて送る。
     * 配布後の新規会員（$append）は件数を足し、判定結果の状態は変えない。
     *
     * @param  list<int>  $memberIds
     */
    private function send(
        AfterRegistrationDelivery $delivery,
        WeatherCouponRun $run,
        array $memberIds,
        CarbonImmutable $date,
        CarbonImmutable $now,
        CarbonImmutable $closesAt,
        bool $append = false,
    ): void {
        DB::transaction(function () use ($delivery, $run, $memberIds, $date, $now, $closesAt, $append): void {
            $recipients = $memberIds;
            $couponDeliveryId = $run->coupon_delivery_id;
            // 空配列は「全会員」を意味するため、対象がいないときは発行しない
            if ($delivery->coupon_id !== null && $memberIds !== []) {
                $recipients = $this->memberBenefit->issueCouponToMembers(
                    (int) $delivery->project_id,
                    (int) $delivery->coupon_id,
                    $memberIds,
                    MemberCouponSource::WEATHER_RAIN,
                    $date->toDateString(),
                    $delivery->isCouponOnly() && $delivery->shouldSendNotification(),
                    // 営業日の終わりまで。日をまたぐ営業は翌日の閉店まで
                    $date->endOfDay()->max($closesAt->subSecond()),
                )['member_ids'];
                $couponDeliveryId = $this->recordCouponDelivery($delivery, $run, $recipients, $date, $now);
            }
            if (! $delivery->isCouponOnly() && $recipients !== []) {
                $this->createAnnouncement($delivery, $recipients);
            }
            $run->update(array_filter([
                'status' => $append ? null : WeatherCouponRun::STATUS_SENT,
                'coupon_delivery_id' => $couponDeliveryId,
                'issued_count' => ($append ? (int) $run->issued_count : 0) + count($recipients),
            ], fn ($value) => $value !== null));
        });
    }

    /** @param  list<int>  $memberIds */
    private function recordCouponDelivery(AfterRegistrationDelivery $delivery, WeatherCouponRun $run, array $memberIds, CarbonImmutable $date, CarbonImmutable $now): int
    {
        $existing = $run->coupon_delivery_id !== null ? CouponDelivery::withoutGlobalScopes()->find($run->coupon_delivery_id) : null;
        if ($existing !== null) {
            $existing->update([
                'issued_member_ids' => array_values(array_unique([...($existing->issued_member_ids ?? []), ...$memberIds])),
                'issued_count' => (int) $existing->issued_count + count($memberIds),
            ]);

            return (int) $existing->id;
        }

        return (int) CouponDelivery::withoutGlobalScopes()->create([
            'project_id' => $delivery->project_id,
            'coupon_id' => $delivery->coupon_id,
            'delivery_type' => CouponDeliveryType::EVENT_WEATHER_RAIN,
            'status' => CouponDeliveryStatus::SENT,
            'sent_at' => $now,
            'issued_member_ids' => $memberIds === [] ? null : $memberIds,
            'issued_count' => count($memberIds),
            'segment_criteria' => ['after_registration_delivery_id' => $delivery->id, 'store_id' => $delivery->intParam('store_id', 0), 'business_date' => $date->toDateString()],
        ])->id;
    }

    /** @param  list<int>  $memberIds */
    private function createAnnouncement(AfterRegistrationDelivery $delivery, array $memberIds): void
    {
        $announcement = Announcement::withoutGlobalScopes()->create([
            'project_id' => $delivery->project_id,
            'title' => (string) $delivery->title,
            'body' => $delivery->body,
            'is_pinned' => false,
            'delivery_type' => Announcement::DELIVERY_TYPE_AFTER_REGISTRATION,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'published_at' => now(),
            'sent_at' => now(),
            'send_notification' => true,
            'notification_pending' => true,
            'notification_retry_at' => now(),
            'target_member_ids' => $memberIds,
            'delivery_coupon_id' => $delivery->coupon_id,
        ]);
        // 送れなかった通知は notification_pending のまま再送の定期処理が拾う
        SendAnnouncementNotificationJob::dispatch((int) $announcement->id)->afterCommit();
    }

    /** 店舗の担当者とプロジェクトの管理者のうち、お知らせを管理できる人にメールで承認を求める */
    private function requestApproval(AfterRegistrationDelivery $delivery, Store $store, WeatherCouponRun $run): void
    {
        $emails = AdminUser::query()
            ->where(fn ($query) => $query
                ->whereHas('stores', fn ($stores) => $stores->whereKey($store->id))
                ->orWhere(fn ($admins) => $admins
                    ->where('role', AdminUser::ROLE_PROJECT_ADMIN)
                    ->where(fn ($project) => $project->where('project_id', $delivery->project_id)->orWhereHas('projects', fn ($projects) => $projects->whereKey($delivery->project_id)))))
            ->whereNotNull('email')
            ->get()
            ->filter(fn (AdminUser $admin) => $admin->hasPermission('announcements.manage'))
            ->pluck('email')
            ->unique();
        foreach ($emails as $email) {
            try {
                // 宛先が他の担当者に見えないよう、1人ずつ新しいメールを作る
                Mail::to($email)->send(new WeatherCouponApprovalMail(
                    (string) $store->name,
                    trim((string) $delivery->title) !== '' ? (string) $delivery->title : (string) $delivery->coupon?->name,
                    (int) $run->max_precipitation_probability,
                    $run->approval_expires_at->format('n月j日 H:i'),
                    route('admin.weather-coupons.index', ['project_key' => $delivery->project->project_key]),
                ));
            } catch (\Throwable $exception) {
                // 1人に届かなくても、他の担当者には送る
                report($exception);
            }
        }
    }

    /** クーポン付きの配信は、クーポンが停止中なら送らない。クーポンのみでクーポンが無いものも送らない */
    private function canSend(AfterRegistrationDelivery $delivery): bool
    {
        if ($delivery->coupon_id === null) {
            return ! $delivery->isCouponOnly();
        }

        return $delivery->coupon?->is_active === true;
    }

    private function businessHours(Store $store, int $projectId): BusinessHours
    {
        $range = $this->storeContext->runForStore($store, fn () => $this->businessSettings->getBusinessHours($projectId));

        return new BusinessHours((string) $range['start'], (string) $range['end']);
    }

    /** @return Builder<WeatherCouponRun> */
    private function visibleRuns(): Builder
    {
        $storeIds = $this->storeContext->visibleStoreIds;
        $projectId = AdminContext::getCurrentProjectId();
        if ($storeIds === null) {
            return WeatherCouponRun::query();
        }
        $deliveryIds = AfterRegistrationDelivery::query()
            ->where('schedule_type', ScheduledDeliveryType::WEATHER_RAIN)
            ->when($projectId !== null, fn ($query) => $query->where('project_id', $projectId))
            ->get()
            ->filter(fn (AfterRegistrationDelivery $delivery) => in_array($delivery->intParam('store_id', 0), $storeIds, true))
            ->pluck('id');

        return WeatherCouponRun::query()->whereIn('after_registration_delivery_id', $deliveryIds);
    }
}
