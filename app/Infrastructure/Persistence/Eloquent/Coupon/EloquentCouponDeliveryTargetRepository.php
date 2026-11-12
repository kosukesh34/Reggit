<?php

namespace App\Infrastructure\Persistence\Eloquent\Coupon;

use App\Domain\Coupon\Repositories\CouponDeliveryTargetRepository;
use App\Enums\MemberNotificationPreference;
use App\Enums\QueueStatus;
use App\Enums\ReservationStatus;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\CouponRedemption;
use App\Models\LineFollowEvent;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\MemberLotteryEntitlement;
use App\Models\MemberRankHistory;
use App\Models\MemberStamp;
use App\Models\MemberSubscription;
use App\Models\MemberTicket;
use App\Models\MemberVisit;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\QueueEntry;
use App\Models\Reservation;
use App\Models\Scopes\StoreAccessScope;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\WalletPassRegistration;
use App\Support\MemberChannel;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class EloquentCouponDeliveryTargetRepository implements CouponDeliveryTargetRepository
{
    public function findIdsByBirthdayMonth(int $projectId, int $month): array
    {
        return Member::query()
            ->where('project_id', $projectId)
            ->whereNotNull('birthday')
            ->whereMonth('birthday', $month)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function findIdsByTagIds(int $projectId, array $tagIds): array
    {
        $normalizedTagIds = array_values(array_unique(array_filter(
            array_map('intval', $tagIds),
            static fn (int $id): bool => $id > 0,
        )));
        if ($normalizedTagIds === []) {
            return [];
        }

        return Member::query()
            ->where('project_id', $projectId)
            ->whereHas('tags', function ($query) use ($projectId, $normalizedTagIds): void {
                $query->where('member_tags.project_id', $projectId)
                    ->whereIn('member_tags.id', $normalizedTagIds);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function findIdsCreatedSince(int $projectId, \DateTimeInterface $since): array
    {
        return Member::query()
            ->where('project_id', $projectId)
            ->where('created_at', '>=', $since)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function findInactiveIds(int $projectId, \DateTimeInterface $since): array
    {
        $userIdsWithRecentOrders = Order::query()
            ->where('project_id', $projectId)
            ->where('created_at', '>=', $since)
            ->notCancelled()
            ->distinct()
            ->pluck('user_id');

        $query = Member::query()->where('project_id', $projectId);

        if ($userIdsWithRecentOrders->isNotEmpty()) {
            $query->whereNotIn('user_id', $userIdsWithRecentOrders->all());
        }

        $query->where(function ($q) use ($since) {
            $q->whereNull('last_visited_at')
                ->orWhere('last_visited_at', '<', $since->format('Y-m-d'));
        });

        return $query->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function findIdsByMinSales(
        int $projectId,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
        float $minTotalAmount,
    ): array {
        $userIds = Order::query()
            ->where('project_id', $projectId)
            ->whereBetween('created_at', [$start, $end])
            ->notCancelled()
            ->selectRaw('user_id, SUM(total_amount) as total')
            ->groupBy('user_id')
            ->havingRaw('SUM(total_amount) >= ?', [(string) $minTotalAmount])
            ->pluck('user_id');

        if ($userIds->isEmpty()) {
            return [];
        }

        return Member::query()
            ->where('project_id', $projectId)
            ->whereIn('user_id', $userIds->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function findIdsWithNoOrdersInPeriod(
        int $projectId,
        \DateTimeInterface $start,
        \DateTimeInterface $end,
    ): array {
        $userIdsWithOrders = Order::query()
            ->where('project_id', $projectId)
            ->whereBetween('created_at', [$start, $end])
            ->distinct()
            ->pluck('user_id');

        $query = Member::query()->where('project_id', $projectId);

        if ($userIdsWithOrders->isNotEmpty()) {
            $query->whereNotIn('user_id', $userIdsWithOrders->all());
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function findIdsByChannel(int $projectId, string $channel): array
    {
        $query = Member::query()->where('project_id', $projectId);

        if ($channel === MemberChannel::LINE) {
            $query->whereHas('user', function ($uq) {
                $uq->whereRaw('line_user_id REGEXP ?', ['^U[0-9a-fA-F]{32}$']);
            });
        } elseif ($channel === MemberChannel::APP) {
            $query->whereHas('user.devices');
        } elseif ($channel === MemberChannel::BOTH) {
            $query->whereHas('user', function ($uq) {
                $uq->whereRaw('line_user_id REGEXP ?', ['^U[0-9a-fA-F]{32}$']);
            })->whereHas('user.devices');
        } else {
            return [];
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function findIdsByVisitCount(int $projectId, ?int $min, ?int $max): array
    {
        if ($min === null && $max === null) {
            return [];
        }

        $orderCountsSub = Order::query()
            ->where('project_id', $projectId)
            ->notCancelled()
            ->selectRaw('user_id, COUNT(*) as visit_count')
            ->groupBy('user_id');

        $query = Member::query()
            ->where('members.project_id', $projectId)
            ->leftJoinSub($orderCountsSub, 'visit_stats', function ($join) {
                $join->on('members.user_id', '=', 'visit_stats.user_id');
            })
            ->select('members.id');

        if ($min !== null) {
            $query->whereRaw('COALESCE(visit_stats.visit_count, 0) >= ?', [$min]);
        }
        if ($max !== null) {
            $query->whereRaw('COALESCE(visit_stats.visit_count, 0) <= ?', [$max]);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function findIdsByLastVisited(int $projectId, string $mode, int $days, ?\DateTimeInterface $referenceDate = null): array
    {
        $days = max(1, $days);
        $boundary = ($referenceDate === null ? Carbon::today() : Carbon::instance($referenceDate)->copy())
            ->subDays($days)->startOfDay();

        $query = Member::query()->where('project_id', $projectId);

        if ($mode === 'inactive_days') {
            $query->where(function ($q) use ($boundary) {
                $q->whereNull('last_visited_at')
                    ->orWhere('last_visited_at', '<', $boundary->format('Y-m-d'));
            });
        } elseif ($mode === 'visited_within_days') {
            $query->whereNotNull('last_visited_at')
                ->where('last_visited_at', '>=', $boundary->format('Y-m-d'));
        } elseif ($mode === 'days_after') {
            $targetDate = $boundary->format('Y-m-d');
            $query->whereNotNull('last_visited_at')
                ->whereDate('last_visited_at', $targetDate);
        } else {
            return [];
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function findIdsBySex(int $projectId, string $sex): array
    {
        return Member::query()
            ->where('project_id', $projectId)
            ->where('sex', $sex)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function findIdsByRank(int $projectId, string $rank): array
    {
        return Member::query()
            ->where('project_id', $projectId)
            ->where('rank', $rank)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function findIdsByStampProgress(
        int $projectId,
        ?int $stampCardId,
        ?int $minStamps,
        bool $completedOnly,
    ): array {
        if ($stampCardId === null && $minStamps === null && $completedOnly === false) {
            return [];
        }

        $query = MemberStamp::query()
            ->where('project_id', $projectId);

        if ($stampCardId !== null && $stampCardId > 0) {
            $query->where('stamp_card_id', $stampCardId);
        }
        if ($minStamps !== null) {
            $query->where('current_stamps', '>=', max(0, $minStamps));
        }
        if ($completedOnly) {
            $query->whereNotNull('completed_at');
        }

        return $query->distinct()
            ->pluck('member_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function findIdsByLifetimeGrantedPoints(int $projectId, ?int $min, ?int $max): array
    {
        if ($min === null && $max === null) {
            return [];
        }

        $query = Member::query()->where('project_id', $projectId);

        if ($min !== null) {
            $query->where('lifetime_granted_points', '>=', $min);
        }
        if ($max !== null) {
            $query->where('lifetime_granted_points', '<=', $max);
        }

        return $query->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function findIdsByPurchasedProduct(int $projectId, int $productId): array
    {
        $fromOrders = Member::query()
            ->where('project_id', $projectId)
            ->whereHas('user.orders', function ($query) use ($projectId, $productId) {
                $query->where('project_id', $projectId)
                    ->notCancelled()
                    ->whereHas('items', function ($itemQuery) use ($productId) {
                        $itemQuery->where('product_id', $productId);
                    });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $product = Product::query()
            ->where('project_id', $projectId)
            ->whereKey($productId)
            ->first();
        $smaregiProductId = is_string($product?->smaregi_product_id) ? trim($product->smaregi_product_id) : '';

        $fromVisits = Member::query()
            ->where('project_id', $projectId)
            ->whereHas('visits.items', function ($query) use ($productId, $smaregiProductId) {
                $query->where(function ($inner) use ($productId, $smaregiProductId) {
                    $inner->where('product_id', $productId);
                    if ($smaregiProductId !== '') {
                        $inner->orWhere('smaregi_product_id', $smaregiProductId);
                    }
                });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge($fromOrders, $fromVisits)));
    }

    public function findIdsByCouponStatus(int $projectId, int $couponId, string $status): array
    {
        if ($couponId <= 0) {
            return [];
        }

        $holders = MemberCoupon::query()
            ->where('project_id', $projectId)
            ->where('coupon_id', $couponId)
            ->select('member_id');
        if ($status === 'not_issued') {
            return Member::query()
                ->where('project_id', $projectId)
                ->whereNotIn('id', $holders)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();
        }

        $now = now();
        $query = match ($status) {
            MemberCoupon::STATUS_USED => $holders->where('status', MemberCoupon::STATUS_USED),
            MemberCoupon::STATUS_UNUSED => $holders->where('status', MemberCoupon::STATUS_UNUSED)
                ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', $now)),
            'expired' => $holders->where('status', MemberCoupon::STATUS_UNUSED)->where('valid_until', '<', $now),
            default => null,
        };
        if ($query === null) {
            return [];
        }

        return $query
            ->distinct()
            ->pluck('member_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function findIdsByProfileField(int $projectId, string $key, array $values, bool $requireAll = false): array
    {
        $expected = collect($values)
            ->map(fn ($value): string => trim((string) $value))
            ->filter(fn (string $value): bool => $value !== '')
            ->unique()
            ->values();
        if ($key === '' || $expected->isEmpty()) {
            return [];
        }

        return Member::query()
            ->where('project_id', $projectId)
            ->whereNotNull('profile_extra')
            ->get(['id', 'profile_extra'])
            ->filter(function (Member $member) use ($key, $expected, $requireAll): bool {
                $actual = data_get($member->profile_extra, $key);
                $actualValues = is_array($actual) ? $actual : [$actual];

                $matched = collect($actualValues)
                    ->map(fn ($value): string => trim((string) $value))
                    ->intersect($expected);

                return $requireAll
                    ? $matched->count() === $expected->count()
                    : $matched->isNotEmpty();
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function findIdsByAnnouncementRead(int $projectId, int $announcementId, string $status): array
    {
        $announcement = Announcement::query()->where('project_id', $projectId)->find($announcementId);
        if ($announcement === null || ! in_array($status, ['read', 'unread'], true)) {
            return [];
        }

        $readerUserIds = AnnouncementRead::query()
            ->where('project_id', $projectId)
            ->where('announcement_id', $announcementId)
            ->select('user_id');
        $query = Member::query()->where('project_id', $projectId);
        if ($status === 'read') {
            $query->whereIn('user_id', $readerUserIds);
        } else {
            // 宛先を記録した配信は宛先の中だけで未読を数える（届いていない会員を未読にしない）
            if (is_array($announcement->target_member_ids)) {
                $query->whereIntegerInRaw('id', array_map('intval', $announcement->target_member_ids));
            }
            $query->where(fn ($q) => $q->whereNull('user_id')->orWhereNotIn('user_id', $readerUserIds));
        }

        return $this->ids($query);
    }

    public function findIdsByRegisteredDate(int $projectId, ?string $from, ?string $to): array
    {
        $query = Member::query()->where('project_id', $projectId);
        if ($from !== null) {
            $query->where('created_at', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to !== null) {
            $query->where('created_at', '<=', Carbon::parse($to)->endOfDay());
        }

        return $this->ids($query);
    }

    public function findIdsByAge(int $projectId, ?int $min, ?int $max): array
    {
        $today = Carbon::today();
        $query = Member::query()->where('project_id', $projectId)->whereNotNull('birthday');
        if ($min !== null) {
            $query->whereDate('birthday', '<=', $today->copy()->subYears($min));
        }
        if ($max !== null) {
            // max 歳以下 = (max + 1) 歳の誕生日をまだ迎えていない
            $query->whereDate('birthday', '>', $today->copy()->subYears($max + 1));
        }

        return $this->ids($query);
    }

    public function findIdsWithoutPointLedgerByPoints(int $projectId, ?int $min, ?int $max): array
    {
        $query = Member::query()
            ->where('project_id', $projectId)
            ->whereNotIn('id', $this->ledgerTransactions($projectId)->select('member_id'));
        if ($min !== null) {
            $query->where('points', '>=', $min);
        }
        if ($max !== null) {
            $query->where('points', '<=', $max);
        }

        return $this->ids($query);
    }

    public function findIdsWithPointLedger(int $projectId, ?int $minEarned): array
    {
        $members = $this->ledgerTransactions($projectId)->select('member_id')->groupBy('member_id');
        if ($minEarned !== null) {
            $members->havingRaw('SUM(CASE WHEN points > 0 THEN points ELSE 0 END) >= ?', [$minEarned]);
        }

        return $this->ids(Member::query()->where('project_id', $projectId)->whereIn('id', $members));
    }

    /**
     * MemberPointLedger と同じく、店舗で絞らない会員共通の台帳。
     *
     * @return Builder<PointTransaction>
     */
    private function ledgerTransactions(int $projectId): Builder
    {
        return PointTransaction::query()->withoutGlobalScope(StoreAccessScope::class)->where('project_id', $projectId);
    }

    public function findIdsByStores(int $projectId, array $storeIds): array
    {
        return $this->ids(Member::query()
            ->where('project_id', $projectId)
            ->whereHas('stores', fn ($query) => $query->whereIn('stores.id', array_map('intval', $storeIds))));
    }

    public function findIdsByUsedStores(int $projectId, array $storeIds): array
    {
        $storeIds = array_map('intval', $storeIds);
        $orders = Order::query()->where('project_id', $projectId)->notCancelled()->whereIn('store_id', $storeIds)->whereNotNull('user_id')->select('user_id');
        $reservations = Reservation::query()->where('project_id', $projectId)->where('status', ReservationStatus::COMPLETED)->whereIn('store_id', $storeIds)->whereNotNull('user_id')->select('user_id');
        $queue = QueueEntry::query()->where('project_id', $projectId)->where('status', QueueStatus::ENTERED)->whereIn('store_id', $storeIds);
        $tickets = MemberTicket::query()->where('project_id', $projectId)
            ->whereHas('uses', fn ($uses) => $uses->whereIn('store_id', $storeIds)->whereNull('cancelled_at'))
            ->select('member_id');

        $visits = MemberVisit::query()->where('project_id', $projectId)->whereIn('store_id', $storeIds)->select('member_id');

        return $this->ids(Member::query()
            ->where('project_id', $projectId)
            ->where(fn ($query) => $query
                ->whereIn('id', $visits)
                ->orWhereIn('user_id', $orders)
                ->orWhereIn('user_id', $reservations)
                ->orWhereIn('id', (clone $queue)->whereNotNull('member_id')->select('member_id'))
                ->orWhereIn('user_id', (clone $queue)->whereNotNull('user_id')->select('user_id'))
                ->orWhereIn('id', $tickets)));
    }

    public function findIdsByAppLastUsed(int $projectId, string $mode, int $days): array
    {
        $boundary = Carbon::today()->subDays($days);
        $usedWithin = fn ($query) => $query->where('project_id', $projectId)->where('last_used_at', '>=', $boundary);
        $query = Member::query()->where('project_id', $projectId);
        if ($mode === 'within_days') {
            $query->whereHas('user.devices', $usedWithin);
        } else {
            // 端末を持たない（LINE だけの）会員は「アプリを使っていない」に含めない
            $query->whereHas('user.devices', fn ($devices) => $devices->where('project_id', $projectId))
                ->whereDoesntHave('user.devices', $usedWithin);
        }

        return $this->ids($query);
    }

    public function findIdsBySurveyResponse(int $projectId, int $surveyId, string $status): array
    {
        if (! Survey::query()->where('project_id', $projectId)->whereKey($surveyId)->exists()) {
            return [];
        }
        $answered = SurveyResponse::query()
            ->where('project_id', $projectId)
            ->where('survey_id', $surveyId)
            ->whereNotNull('answered_at')
            ->select('member_id');
        $query = Member::query()->where('project_id', $projectId);
        $status === 'answered' ? $query->whereIn('id', $answered) : $query->whereNotIn('id', $answered);

        return $this->ids($query);
    }

    public function findIdsByReservation(int $projectId, string $mode, int $days): array
    {
        $userIds = Reservation::query()
            ->where('project_id', $projectId)
            ->where('status', '!=', ReservationStatus::CANCELLED)
            ->where('reserved_at', '>=', Carbon::today()->subDays($days))
            ->whereNotNull('user_id')
            ->select('user_id');
        $query = Member::query()->where('project_id', $projectId);
        if ($mode === 'has') {
            $query->whereIn('user_id', $userIds);
        } else {
            $query->where(fn ($q) => $q->whereNull('user_id')->orWhereNotIn('user_id', $userIds));
        }

        return $this->ids($query);
    }

    public function findIdsByAcquisitionSources(int $projectId, array $sourceIds): array
    {
        return $this->ids(Member::query()
            ->where('project_id', $projectId)
            ->whereIn('acquisition_source_id', array_map('intval', $sourceIds)));
    }

    public function findIdsBySubscriptionStatus(int $projectId, string $status): array
    {
        $subscribers = MemberSubscription::query()->where('project_id', $projectId)->select('member_id');
        $active = (clone $subscribers)->whereNotIn('status', MemberSubscription::ENDED_STATUSES);
        $query = Member::query()->where('project_id', $projectId);
        match ($status) {
            'active' => $query->whereIn('id', $active),
            'ended' => $query->whereIn('id', $subscribers)->whereNotIn('id', $active),
            default => $query->whereNotIn('id', $subscribers),
        };

        return $this->ids($query);
    }

    public function findIdsByTicketStatus(int $projectId, string $status): array
    {
        // MemberTicket::status() が active になる条件と同じ
        $usable = MemberTicket::query()
            ->where('project_id', $projectId)
            ->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('total_uses')->orWhereColumn('used_count', '<', 'total_uses'))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->select('member_id');
        $query = Member::query()->where('project_id', $projectId);
        $status === 'active' ? $query->whereIn('id', $usable) : $query->whereNotIn('id', $usable);

        return $this->ids($query);
    }

    public function findIdsByCouponUseCount(int $projectId, ?int $min, ?int $max): array
    {
        $uses = CouponRedemption::query()
            ->where('project_id', $projectId)
            ->whereNotNull('member_id')
            ->selectRaw('member_id, COUNT(*) as use_count')
            ->groupBy('member_id');
        $query = Member::query()
            ->where('members.project_id', $projectId)
            ->leftJoinSub($uses, 'coupon_uses', 'coupon_uses.member_id', '=', 'members.id');
        if ($min !== null) {
            $query->whereRaw('COALESCE(coupon_uses.use_count, 0) >= ?', [$min]);
        }
        if ($max !== null) {
            $query->whereRaw('COALESCE(coupon_uses.use_count, 0) <= ?', [$max]);
        }

        return $query->pluck('members.id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function findIdsByRankChangedWithin(int $projectId, int $days): array
    {
        return $this->ids(Member::query()
            ->where('project_id', $projectId)
            ->whereIn('id', MemberRankHistory::query()
                ->where('project_id', $projectId)
                ->where('achieved_at', '>=', Carbon::today()->subDays($days))
                ->select('member_id')));
    }

    public function findIdsByWalletStatus(int $projectId, string $status): array
    {
        $registered = WalletPassRegistration::query()->where('project_id', $projectId)->select('member_id');
        $query = Member::query()->where('project_id', $projectId);
        $status === 'registered' ? $query->whereIn('id', $registered) : $query->whereNotIn('id', $registered);

        return $this->ids($query);
    }

    public function findIdsByBirthdaySoon(int $projectId, string $mode, int $days): array
    {
        $today = Carbon::today();
        $dates = $mode === 'exact'
            ? [$today->copy()->addDays($days)]
            : array_map(fn (int $offset) => $today->copy()->addDays($offset), range(0, $days));
        $monthDays = [];
        foreach ($dates as $date) {
            $monthDays[] = [$date->month, $date->day];
            // うるう年でない年は 2/29 生まれを 2/28 に祝う
            if ($date->month === 2 && $date->day === 28 && ! $date->isLeapYear()) {
                $monthDays[] = [2, 29];
            }
        }

        return $this->ids(Member::query()
            ->where('project_id', $projectId)
            ->whereNotNull('birthday')
            ->where(function ($query) use ($monthDays): void {
                foreach ($monthDays as [$month, $day]) {
                    $query->orWhere(fn ($q) => $q->whereMonth('birthday', $month)->whereDay('birthday', $day));
                }
            }));
    }

    public function findIdsByLifetimeSales(int $projectId, ?int $min, ?int $max): array
    {
        // 売上条件・RFM と同じく、キャンセル以外の注文の合計（POS 会計も注文として入る）
        $sales = Order::query()
            ->where('project_id', $projectId)
            ->notCancelled()
            ->whereNotNull('user_id')
            ->selectRaw('user_id, SUM(total_amount) as total_sales')
            ->groupBy('user_id');
        $query = Member::query()
            ->where('members.project_id', $projectId)
            ->leftJoinSub($sales, 'lifetime_sales', 'lifetime_sales.user_id', '=', 'members.user_id');
        if ($min !== null) {
            $query->whereRaw('COALESCE(lifetime_sales.total_sales, 0) >= ?', [$min]);
        }
        if ($max !== null) {
            $query->whereRaw('COALESCE(lifetime_sales.total_sales, 0) <= ?', [$max]);
        }

        return $query->pluck('members.id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function findIdsByOrderTypes(int $projectId, array $orderTypes): array
    {
        return $this->ids(Member::query()
            ->where('project_id', $projectId)
            ->whereIn('user_id', Order::query()
                ->where('project_id', $projectId)
                ->notCancelled()
                ->whereIn('order_type', $orderTypes)
                ->whereNotNull('user_id')
                ->select('user_id')));
    }

    public function findIdsByQueueWithin(int $projectId, int $days): array
    {
        $entries = QueueEntry::query()
            ->where('project_id', $projectId)
            ->where('status', '!=', QueueStatus::CANCELLED)
            ->where('created_at', '>=', Carbon::today()->subDays($days));

        return $this->ids(Member::query()
            ->where('project_id', $projectId)
            ->where(fn ($query) => $query
                ->whereIn('id', (clone $entries)->whereNotNull('member_id')->select('member_id'))
                ->orWhereIn('user_id', (clone $entries)->whereNotNull('user_id')->select('user_id'))));
    }

    public function findIdsWithPointsExpiringCandidates(int $projectId, int $days): array
    {
        // members.points は失効しても減らないことがあるため見ない。残っているかは台帳の計算で決める
        return $this->ids(Member::query()
            ->where('project_id', $projectId)
            ->whereIn('id', $this->ledgerTransactions($projectId)
                ->where('points', '>', 0)
                // expires_at は日付の列で、その日の終わりまで使える（PointLedgerBalanceCalculator と同じ）
                ->whereBetween('expires_at', [Carbon::today()->toDateString(), Carbon::today()->addDays($days)->toDateString()])
                ->select('member_id')));
    }

    public function findIdsByCouponExpiring(int $projectId, int $days): array
    {
        return $this->ids(Member::query()
            ->where('project_id', $projectId)
            ->whereIn('id', MemberCoupon::query()
                ->where('project_id', $projectId)
                ->where('status', MemberCoupon::STATUS_UNUSED)
                ->whereBetween('valid_until', [now(), Carbon::today()->addDays($days)->endOfDay()])
                ->select('member_id')));
    }

    public function findIdsByLotteryStatus(int $projectId, string $status): array
    {
        // MemberWalletSummaryService の「未抽選の数」と同じ条件
        $pending = MemberLotteryEntitlement::query()
            ->where('project_id', $projectId)
            ->where('is_done', false)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->select('member_id');
        $query = Member::query()->where('project_id', $projectId);
        $status === 'pending' ? $query->whereIn('id', $pending) : $query->whereNotIn('id', $pending);

        return $this->ids($query);
    }

    public function findIdsByNpsCategory(int $projectId, string $category): array
    {
        [$low, $high] = match ($category) {
            'promoter' => [9, 10],
            'passive' => [7, 8],
            default => [0, 6],
        };
        // 会員ごとに最新の NPS 回答だけを見る（別名を使うため、グローバルスコープのない DB::table で組む）
        $latest = DB::table('survey_responses as r')
            ->where('r.project_id', $projectId)
            ->whereNotNull('r.nps_score')
            ->whereNotNull('r.answered_at')
            ->whereNotExists(fn ($newer) => $newer->selectRaw('1')
                ->from('survey_responses as newer')
                ->whereColumn('newer.member_id', 'r.member_id')
                ->whereNotNull('newer.nps_score')
                ->where(fn ($order) => $order->whereColumn('newer.answered_at', '>', 'r.answered_at')
                    ->orWhere(fn ($tie) => $tie->whereColumn('newer.answered_at', 'r.answered_at')->whereColumn('newer.id', '>', 'r.id'))))
            ->whereBetween('r.nps_score', [$low, $high])
            ->select('r.member_id');

        return $this->ids(Member::query()->where('project_id', $projectId)->whereIn('id', $latest));
    }

    public function findIdsByLineFollowWithin(int $projectId, int $days): array
    {
        return $this->ids(Member::query()
            ->where('project_id', $projectId)
            ->whereIn('user_id', LineFollowEvent::query()
                ->where('project_id', $projectId)
                ->where('type', LineFollowEvent::TYPE_FOLLOW)
                ->where('created_at', '>=', Carbon::today()->subDays($days))
                ->whereNotNull('user_id')
                ->select('user_id')));
    }

    public function findIdsByNotificationPreference(int $projectId, string $preference): array
    {
        $query = Member::query()->where('project_id', $projectId);
        if ($preference === MemberNotificationPreference::ALL) {
            // 未設定・不明な値は「すべて受け取る」として扱う（MemberNotificationPreference::normalize と同じ）
            $query->where(fn ($q) => $q->whereNull('notification_preference')
                ->orWhereNotIn('notification_preference', [MemberNotificationPreference::COUPON_ONLY, MemberNotificationPreference::NONE]));
        } else {
            $query->where('notification_preference', $preference);
        }

        return $this->ids($query);
    }

    public function findIdsByLineBlockStatus(int $projectId, string $status): array
    {
        return $this->ids(Member::query()
            ->where('project_id', $projectId)
            ->whereHas('user', function ($user) use ($status): void {
                $user->whereRaw('line_user_id REGEXP ?', ['^U[0-9a-fA-F]{32}$']);
                $status === 'blocked' ? $user->whereNotNull('line_blocked_at') : $user->whereNull('line_blocked_at');
            }));
    }

    /**
     * @param  Builder<Member>  $query
     * @return list<int>
     */
    private function ids($query): array
    {
        return $query->pluck('id')->map(fn ($id) => (int) $id)->values()->all();
    }

    public function countAll(int $projectId): int
    {
        return Member::query()->where('project_id', $projectId)->count();
    }

    public function findAllIds(int $projectId): array
    {
        return Member::query()
            ->where('project_id', $projectId)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    public function filterValidIds(int $projectId, array $memberIds): array
    {
        if ($memberIds === []) {
            return [];
        }

        return Member::query()
            ->where('project_id', $projectId)
            ->whereIn('id', $memberIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }
}
