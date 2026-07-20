<?php

namespace App\Services\Admin;

use App\Domain\Coupon\Repositories\MemberCouponRepository;
use App\Domain\Coupon\ValueObjects\PendingIssuedCoupon;
use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Enums\MemberVisitSource;
use App\Enums\OrderStatus;
use App\Enums\PointTransactionType;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\MemberRankHistory;
use App\Models\MemberStamp;
use App\Models\MemberStampTransaction;
use App\Models\MemberVisit;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Scopes\ProjectScope;
use App\Models\StampCard;
use App\Models\User;
use App\Services\MemberPointsExpiryCalculator;
use App\Services\MemberRankCalculator;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerPointsInboundSyncService;
use App\Services\Smaregi\SmaregiCustomerUpdatePayloadBuilder;
use App\Services\UserService;
use App\Support\MemberChannel;
use App\Util\LogHelper;
use App\Util\StrHelper;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MemberAdminService
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
        private SmaregiCustomerUpdatePayloadBuilder $smaregiCustomerUpdatePayloadBuilder,
        private SmaregiCustomerPointsInboundSyncService $smaregiCustomerPointsInboundSyncService,
        private MemberRankCalculator $rankCalculator,
        private MemberPointsExpiryCalculator $expiryCalculator,
        private MemberCouponRepository $memberCouponRepository,
        private ShopSettingRepository $shopSettingRepository,
        private UserService $userService,
    ) {}

    public function getPaginatedList(int $projectId, int $perPage = 20, array $filters = []): LengthAwarePaginator
    {
        $query = Member::with(['user.devices'])
            ->where('project_id', $projectId);

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('member_number', 'like', '%'.$keyword.'%')
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', '%'.$keyword.'%')
                        ->orWhere('phone', 'like', '%'.$keyword.'%'));
            });
        }

        $status = (string) ($filters['status'] ?? '');
        if ($status !== '') {
            $query->where('status', $status);
        }

        $rank = (string) ($filters['rank'] ?? '');
        if ($rank !== '') {
            $query->where('rank', $rank);
        }

        $channel = (string) ($filters['channel'] ?? '');
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
        }

        return $query->orderByDesc('created_at')->paginate($perPage);
    }

    public function findOrFail(int $id, ?int $projectId = null): Member
    {
        $query = Member::with([
            'user',
            'user.devices',
            'tags',
            'memberStamps.stampCard',
            'memberCoupons' => fn ($q) => $q->orderByDesc('issued_at'),
            'memberCoupons.coupon',
        ]);
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }
        $member = $query->findOrFail($id);

        return $this->prepareMemberForDisplay($member);
    }

    /**
     * 更新・付与などの書き込み処理用。スマレジへのインバウンド同期は行わない（ローカル変更の上書きを防ぐ）。
     */
    public function findForWrite(int $id, ?int $projectId = null): Member
    {
        $query = Member::with(['user', 'pointTransactions.order', 'memberStamps.stampCard', 'memberCoupons.coupon']);
        if ($projectId !== null) {
            $query->where('project_id', $projectId);
        }

        $member = $query->findOrFail($id);
        $member->syncPointsLedger();
        $member->refresh();

        return $member;
    }

    private function prepareMemberForDisplay(Member $member): Member
    {
        $member->syncPointsLedger();
        $member->refresh();
        $this->maybeSyncInboundPointsFromSmaregi($member);
        $member->refresh();

        $rankInfo = $this->rankCalculator->getRankInfo($member);
        $member->setAttribute('rank_label', $rankInfo['current_rank_label'] ?? $member->rank);
        $member->setAttribute('points_expiry', $this->expiryCalculator->getExpiryInfo($member));

        return $member;
    }

    /**
     * 会員の購買サマリー（LTV・注文数・平均単価・最終購買日・最終来店からの経過日数）
     * アプリ注文に加え、スマレジPOS来店（購入額付き）も含める。
     *
     * @return array{
     *     total_orders: int,
     *     total_revenue: float,
     *     avg_order_value: float,
     *     last_order_at: ?Carbon,
     *     days_since_last_order: int|null,
     *     recent_orders: Collection
     * }
     */
    public function getPurchaseSummary(Member $member): array
    {
        $userId = $member->user_id;
        $orderRevenue = 0.0;
        $orderCount = 0;
        $lastOrderAt = null;
        $recentOrders = collect();

        if ($userId !== null) {
            $baseQuery = Order::where('user_id', $userId)
                ->where('status', '!=', OrderStatus::CANCELLED);
            $orderCount = $baseQuery->count();
            $orderRevenue = (float) $baseQuery->sum('total_amount');
            $lastOrder = Order::where('user_id', $userId)
                ->where('status', '!=', OrderStatus::CANCELLED)
                ->orderByDesc('created_at')
                ->first();
            $lastOrderAt = $lastOrder?->created_at;
            $recentOrders = Order::with('items.product')
                ->where('user_id', $userId)
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        }

        $posVisits = MemberVisit::query()
            ->where('member_id', $member->id)
            ->where('source', MemberVisitSource::SMAREGI)
            ->whereNotNull('amount')
            ->get(['amount', 'visited_at']);
        $posRevenue = (float) $posVisits->sum('amount');
        $posPurchaseCount = $posVisits->filter(fn (MemberVisit $visit) => (float) $visit->amount > 0)->count();
        $lastPosAt = $posVisits->max('visited_at');

        $totalRevenue = $orderRevenue + $posRevenue;
        $totalOrders = $orderCount + $posPurchaseCount;
        $avgOrderValue = $totalOrders > 0 ? round($totalRevenue / $totalOrders) : 0;

        $lastPurchaseAt = $lastOrderAt;
        if ($lastPosAt !== null && ($lastPurchaseAt === null || $lastPosAt->gt($lastPurchaseAt))) {
            $lastPurchaseAt = $lastPosAt;
        }
        $daysSinceLastOrder = $lastPurchaseAt !== null ? (int) $lastPurchaseAt->diffInDays(now()) : null;

        return [
            'total_orders' => $totalOrders,
            'total_revenue' => $totalRevenue,
            'avg_order_value' => $avgOrderValue,
            'last_order_at' => $lastPurchaseAt,
            'days_since_last_order' => $daysSinceLastOrder,
            'recent_orders' => $recentOrders,
        ];
    }

    /** 配布用の有効クーポン一覧（同一プロジェクト） */
    public function getActiveCouponsForSelect(int $projectId): Collection
    {
        return Coupon::where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /** 配布用の有効スタンプカード一覧（同一プロジェクト） */
    public function getActiveStampCardsForSelect(int $projectId): Collection
    {
        return StampCard::where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /** 会員にクーポンを1件発行（管理画面からの手動配布） */
    public function issueCouponToMember(Member $member, int $couponId, ?\DateTimeInterface $validFrom = null, ?\DateTimeInterface $validUntil = null): MemberCoupon
    {
        $coupon = Coupon::where('project_id', $member->project_id)->findOrFail($couponId);
        $now = now();
        $validFrom = $validFrom ?? $now;
        $validUntil = $validUntil ?? $coupon->end_at ?? Carbon::now()->addDays(30);

        $this->memberCouponRepository->issue(new PendingIssuedCoupon(
            (int) $member->project_id,
            (int) $member->id,
            (int) $coupon->id,
            (string) $coupon->name,
            $now,
            $validFrom,
            $validUntil instanceof \DateTimeInterface ? $validUntil : null,
        ));

        return MemberCoupon::query()
            ->where('member_id', $member->id)
            ->where('coupon_id', $coupon->id)
            ->orderByDesc('id')
            ->firstOrFail();
    }

    /**
     * 複数会員にクーポンを一括発行（プレゼント配信）。$memberIds が空の場合は全員に配布。
     *
     * @param  list<int>  $memberIds
     * @return array{count: int, member_ids: list<int>}
     */
    public function issueCouponToMembers(int $projectId, int $couponId, array $memberIds = []): array
    {
        $coupon = Coupon::where('project_id', $projectId)->findOrFail($couponId);
        $query = Member::where('project_id', $projectId);
        if ($memberIds !== []) {
            $query->whereIn('id', $memberIds);
        }
        $members = $query->get();
        $validUntil = $coupon->end_at ?? Carbon::now()->addDays(30);
        $now = now();
        $issuedMemberIds = [];
        foreach ($members as $member) {
            if ($this->memberCouponRepository->hasUnusedValid(
                (int) $member->id,
                $couponId,
                $now,
                $projectId,
            )) {
                continue;
            }
            $this->issueCouponToMember($member, $couponId, null, $validUntil);
            $issuedMemberIds[] = (int) $member->id;
        }

        return [
            'count' => count($issuedMemberIds),
            'member_ids' => $issuedMemberIds,
        ];
    }

    /** 会員のスタンプカードにスタンプを追加（注文・管理画面からの付与） */
    public function addStampsToMember(
        Member $member,
        int $stampCardId,
        int $count = 1,
        ?string $description = null,
        ?int $orderId = null
    ): MemberStamp {
        if ($count < 1) {
            throw new \InvalidArgumentException('スタンプ数は1以上を指定してください');
        }

        return $this->adjustStampsToMember(
            $member,
            $stampCardId,
            $count,
            $description ?? 'スタンプ付与',
            $orderId
        );
    }

    /**
     * スタンプを加減算する。delta は 0 以外。残高は 0 未満にならない。
     */
    public function adjustStampsToMember(
        Member $member,
        int $stampCardId,
        int $delta,
        ?string $description = null,
        ?int $orderId = null
    ): MemberStamp {
        if ($delta === 0) {
            throw new \InvalidArgumentException('スタンプ数は0以外を指定してください');
        }

        $stampCard = StampCard::where('project_id', $member->project_id)->findOrFail($stampCardId);
        $memberStamp = MemberStamp::firstOrCreate(
            [
                'project_id' => $member->project_id,
                'member_id' => $member->id,
                'stamp_card_id' => $stampCard->id,
            ],
            ['current_stamps' => 0]
        );
        if ($memberStamp->valid_until === null && $stampCard->valid_days !== null && (int) $stampCard->valid_days > 0) {
            $memberStamp->update(['valid_until' => Carbon::now()->addDays((int) $stampCard->valid_days)]);
        }

        $newBalance = max(0, (int) $memberStamp->current_stamps + $delta);
        $appliedDelta = $newBalance - (int) $memberStamp->current_stamps;
        $memberStamp->update(['current_stamps' => $newBalance]);
        $memberStamp->refresh();

        $required = (int) $stampCard->required_stamps;
        if ($newBalance >= $required && $memberStamp->completed_at === null) {
            $memberStamp->update(['completed_at' => now()]);
        } elseif ($newBalance < $required && $memberStamp->completed_at !== null) {
            $memberStamp->update(['completed_at' => null]);
        }

        if ($appliedDelta !== 0) {
            MemberStampTransaction::create([
                'project_id' => $member->project_id,
                'member_id' => $member->id,
                'stamp_card_id' => $stampCard->id,
                'delta' => $appliedDelta,
                'balance_after' => $newBalance,
                'description' => $description,
                'order_id' => $orderId,
            ]);
        }

        return $memberStamp->refresh();
    }

    /**
     * 来店を記録し、最終来店日を更新する。
     */
    public function recordVisit(
        Member $member,
        \DateTimeInterface $visitedAt,
        string $source,
        ?int $orderId = null,
        ?string $note = null,
        ?float $amount = null,
        ?string $smaregiTransactionHeadId = null,
    ): MemberVisit {
        $visitedCarbon = Carbon::parse($visitedAt);
        $visit = MemberVisit::create([
            'project_id' => $member->project_id,
            'member_id' => $member->id,
            'visited_at' => $visitedCarbon,
            'source' => $source,
            'order_id' => $orderId,
            'note' => $note,
            'amount' => $amount,
            'smaregi_transaction_head_id' => $smaregiTransactionHeadId,
        ]);

        $visitedDate = $visitedCarbon->toDateString();
        $currentLast = $member->last_visited_at;
        if ($currentLast === null || $visitedDate > $currentLast->format('Y-m-d')) {
            $member->update(['last_visited_at' => $visitedDate]);
            $member->refresh();
        }

        return $visit;
    }

    /**
     * 会員に発行済みの未使用クーポンを削除する。
     *
     * @throws \RuntimeException 使用済み・他会員のクーポンの場合
     */
    public function deleteMemberCoupon(Member $member, int $memberCouponId): void
    {
        $memberCoupon = MemberCoupon::query()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->whereKey($memberCouponId)
            ->firstOrFail();

        if ($memberCoupon->isUsed()) {
            throw new \RuntimeException('使用済みクーポンは削除できません');
        }

        $memberCoupon->delete();
    }

    public function getCountByProject(int $projectId): int
    {
        return Member::where('project_id', $projectId)->count();
    }

    /**
     * @param  int[]  $memberIds
     * @return Collection<int, int>
     */
    public function filterValidMemberIds(int $projectId, array $memberIds): Collection
    {
        return Member::where('project_id', $projectId)
            ->whereIn('id', $memberIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * @return Collection<int, array{id: int, member_number: string, name: string}>
     */
    public function searchByKeyword(int $projectId, string $keyword, int $limit = 20): Collection
    {
        $query = Member::query()
            ->where('project_id', $projectId)
            ->with(['user:id,name']);

        if ($keyword !== '') {
            $query->where(function ($inner) use ($keyword) {
                $inner->where('member_number', 'like', '%'.$keyword.'%')
                    ->orWhereHas('user', function ($userQuery) use ($keyword) {
                        $userQuery->where('name', 'like', '%'.$keyword.'%');
                    });
            });
        }

        return $query
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn (Member $member) => [
                'id' => (int) $member->id,
                'member_number' => (string) ($member->member_number ?? ''),
                'name' => (string) ($member->user?->name ?? ''),
            ])
            ->values();
    }

    /** 会員編集フォーム用：ランク選択肢（key => label） */
    private function maybeSyncInboundPointsFromSmaregi(Member $member): void
    {
        if (config('smaregi.inbound_sync_on_member_fetch', true) !== true) {
            return;
        }

        $ttl = (int) config('smaregi.inbound_sync_on_member_fetch_ttl_seconds', 30);
        $cacheKey = 'smaregi_member_fetch_sync:'.(int) $member->id;
        if ($ttl > 0 && Cache::has($cacheKey)) {
            return;
        }

        try {
            $this->smaregiCustomerPointsInboundSyncService->syncMemberFromSmaregi($member);
            if ($ttl > 0) {
                Cache::put($cacheKey, 1, $ttl);
            }
        } catch (\Throwable $e) {
            LogHelper::warning('[AdminMember] smaregi inbound sync on detail failed', [
                'member_id' => (int) $member->id,
                'project_id' => (int) $member->project_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function getRankOptionsForSelect(int $projectId): array
    {
        $ranks = $this->shopSettingRepository->getMemberRankSettings($projectId);
        $options = [];
        foreach ($ranks as $r) {
            if (StrHelper::isEmptyStringOrNull($r['key'] ?? null)) {
                continue;
            }
            $options[(string) $r['key']] = (string) ($r['label'] ?? $r['key']);
        }

        return $options;
    }

    private function recordRankHistory(
        Member $member,
        string $fromRank,
        string $toRank,
        ?string $fromLabel,
        string $toLabel,
    ): void {
        MemberRankHistory::create([
            'project_id' => (int) $member->project_id,
            'member_id' => (int) $member->id,
            'from_rank' => $fromRank !== '' ? $fromRank : null,
            'to_rank' => $toRank,
            'from_rank_label' => $fromLabel,
            'to_rank_label' => $toLabel,
            'achieved_at' => now(),
        ]);
    }

    /**
     * 会員のユーザー情報・会員情報を一括更新する。
     * ローカルを先に保存し、スマレジ連携はベストエフォート（失敗してもローカル更新は維持）。
     */
    public function updateMemberProfile(Member $member, array $userData, array $memberData): void
    {
        $user = $member->user;
        $allowedUser = ['name', 'kana', 'email', 'phone'];
        $updateUser = array_intersect_key($userData, array_flip($allowedUser));
        foreach (['name', 'kana', 'phone'] as $key) {
            if (array_key_exists($key, $updateUser) && $updateUser[$key] === '') {
                $updateUser[$key] = null;
            }
        }
        if (array_key_exists('email', $updateUser) && $updateUser['email'] === '') {
            $updateUser['email'] = null;
        }

        if ($user !== null && $updateUser !== []) {
            $user->update($updateUser);
            $user->refresh();
        }

        $allowedMember = [
            'member_number', 'status', 'rank', 'birthday', 'address',
            'postal_code', 'prefecture', 'city', 'street', 'sex', 'profile_extra',
        ];
        $updateMember = array_intersect_key($memberData, array_flip($allowedMember));
        $updateMember = array_filter(
            $updateMember,
            static fn ($value, string $key): bool => $key === 'profile_extra' || $value !== null,
            ARRAY_FILTER_USE_BOTH
        );
        $previousRank = (string) ($member->rank ?? '');
        if ($updateMember !== []) {
            $member->offsetUnset('rank_label');
            $member->offsetUnset('points_expiry');
            $member->update($updateMember);
            $member->refresh();
            if (array_key_exists('rank', $updateMember)) {
                $newRank = (string) $updateMember['rank'];
                if ($newRank !== '' && $newRank !== $previousRank) {
                    $options = $this->getRankOptionsForSelect((int) $member->project_id);
                    $this->recordRankHistory(
                        $member,
                        $previousRank,
                        $newRank,
                        $previousRank !== '' ? ($options[$previousRank] ?? $previousRank) : null,
                        $options[$newRank] ?? $newRank,
                    );
                }
            }
        }

        $smaregiCustomerId = trim((string) ($member->smaregi_customer_id ?? ''));
        $contractId = $this->smaregiApi->getContractIdForProject($member->project_id);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        if ($contractId === '' || $smaregiCustomerId === '' || $user === null) {
            return;
        }

        $clientId = config('smaregi.client_id');
        $clientSecret = config('smaregi.client_secret');
        if (! is_string($clientId) || $clientId === '' || ! is_string($clientSecret) || $clientSecret === '') {
            LogHelper::warning('[AdminMember] smaregi profile sync skipped: credentials missing', [
                'member_id' => (int) $member->id,
                'project_id' => (int) $member->project_id,
            ]);

            return;
        }

        $mergedUser = array_merge($user->only(['name', 'kana', 'email', 'phone']), $updateUser);
        $body = $this->smaregiCustomerUpdatePayloadBuilder->buildFromAdminMemberData($mergedUser, $member->only([
            'member_number', 'birthday', 'address', 'postal_code', 'sex',
        ]));
        if ($body === []) {
            return;
        }

        $this->smaregiApi->clearLastErrorDetail();
        $result = $this->smaregiApi->updateCustomer($contractId, $smaregiCustomerId, $body);
        if ($result === null) {
            LogHelper::warning('[AdminMember] smaregi profile sync failed after local update', [
                'member_id' => (int) $member->id,
                'project_id' => (int) $member->project_id,
                'smaregi_customer_id' => $smaregiCustomerId,
                'detail' => $this->smaregiApi->getLastErrorDetail(),
            ]);
        }
    }

    public const HISTORY_PER_PAGE = 20;

    public function getPaginatedVisits(
        int $memberId,
        string $keyword = '',
        int $perPage = self::HISTORY_PER_PAGE,
        string $pageName = 'visits_page',
        string $from = '',
        string $to = '',
    ): LengthAwarePaginator {
        $query = MemberVisit::query()
            ->where('member_id', $memberId)
            ->with(['order', 'items'])
            ->orderByDesc('visited_at');

        $this->applyHistoryKeyword($query, $keyword, function ($q, string $like, string $keyword) {
            $sourceKeys = $this->matchEnumKeys(MemberVisitSource::getValues(), $keyword);
            $q->where('note', 'like', $like)
                ->orWhere('smaregi_transaction_head_id', 'like', $like)
                ->orWhereHas('items', fn ($iq) => $iq->where('product_name', 'like', $like))
                ->orWhereHas('order', fn ($oq) => $oq->where('order_number', 'like', $like));
            if ($sourceKeys !== []) {
                $q->orWhereIn('source', $sourceKeys);
            }
        });
        $this->applyDateRange($query, 'visited_at', $from, $to);

        return $query->paginate($perPage, ['*'], $pageName);
    }

    public function getPaginatedPointTransactions(
        int $memberId,
        string $keyword = '',
        int $perPage = self::HISTORY_PER_PAGE,
        string $pageName = 'points_page',
        string $from = '',
        string $to = '',
    ): LengthAwarePaginator {
        $query = PointTransaction::query()
            ->where('member_id', $memberId)
            ->with('order')
            ->orderByDesc('created_at');

        $this->applyHistoryKeyword($query, $keyword, function ($q, string $like, string $keyword) {
            $typeKeys = $this->matchEnumKeys(PointTransactionType::getValues(), $keyword);
            $q->where('description', 'like', $like)
                ->orWhereHas('order', fn ($oq) => $oq->where('order_number', 'like', $like));
            if ($typeKeys !== []) {
                $q->orWhereIn('type', $typeKeys);
            }
        });
        $this->applyDateRange($query, 'created_at', $from, $to);

        return $query->paginate($perPage, ['*'], $pageName);
    }

    public function getPaginatedMemberCoupons(
        int $memberId,
        string $keyword = '',
        int $perPage = self::HISTORY_PER_PAGE,
        string $pageName = 'coupons_page',
        string $from = '',
        string $to = '',
    ): LengthAwarePaginator {
        $query = MemberCoupon::query()
            ->where('member_id', $memberId)
            ->with('coupon')
            ->orderByDesc('issued_at');

        $this->applyHistoryKeyword($query, $keyword, function ($q, string $like, string $keyword) {
            $statusKeys = [];
            if ($keyword === 'unused' || mb_strpos($keyword, '未使用') !== false) {
                $statusKeys[] = 'unused';
            }
            if ($keyword === 'used' || mb_strpos($keyword, '使用済') !== false) {
                $statusKeys[] = 'used';
            }
            $q->where('coupon_name_snapshot', 'like', $like)
                ->orWhereHas('coupon', fn ($cq) => $cq->where('name', 'like', $like)->orWhere('code', 'like', $like));
            if ($statusKeys !== []) {
                $q->orWhereIn('status', $statusKeys);
            }
        });
        $this->applyDateRange($query, 'issued_at', $from, $to);

        return $query->paginate($perPage, ['*'], $pageName);
    }

    public function getPaginatedStampTransactions(
        int $memberId,
        string $keyword = '',
        int $perPage = self::HISTORY_PER_PAGE,
        string $pageName = 'stamps_page',
        string $from = '',
        string $to = '',
    ): LengthAwarePaginator {
        $query = MemberStampTransaction::query()
            ->where('member_id', $memberId)
            ->with('stampCard')
            ->orderByDesc('created_at');

        $this->applyHistoryKeyword($query, $keyword, function ($q, string $like) {
            $q->where('description', 'like', $like)
                ->orWhereHas('stampCard', fn ($sq) => $sq->where('name', 'like', $like));
        });
        $this->applyDateRange($query, 'created_at', $from, $to);

        return $query->paginate($perPage, ['*'], $pageName);
    }

    public function getPaginatedRankHistories(
        int $memberId,
        string $keyword = '',
        int $perPage = self::HISTORY_PER_PAGE,
        string $pageName = 'rank_page',
        string $from = '',
        string $to = '',
    ): LengthAwarePaginator {
        $query = MemberRankHistory::query()
            ->where('member_id', $memberId)
            ->orderByDesc('achieved_at');

        $this->applyHistoryKeyword($query, $keyword, function ($q, string $like) {
            $q->where('from_rank', 'like', $like)
                ->orWhere('to_rank', 'like', $like)
                ->orWhere('from_rank_label', 'like', $like)
                ->orWhere('to_rank_label', 'like', $like);
        });
        $this->applyDateRange($query, 'achieved_at', $from, $to);

        return $query->paginate($perPage, ['*'], $pageName);
    }

    public function getPaginatedCouponRedemptions(
        int $memberId,
        string $keyword = '',
        int $perPage = self::HISTORY_PER_PAGE,
        string $pageName = 'redemptions_page',
        string $from = '',
        string $to = '',
    ): LengthAwarePaginator {
        $query = CouponRedemption::query()
            ->where('member_id', $memberId)
            ->with('coupon')
            ->orderByDesc('used_at');

        $this->applyHistoryKeyword($query, $keyword, function ($q, string $like) {
            $q->whereHas('coupon', fn ($cq) => $cq->where('name', 'like', $like)->orWhere('code', 'like', $like));
        });
        $this->applyDateRange($query, 'used_at', $from, $to);

        return $query->paginate($perPage, ['*'], $pageName);
    }

    /**
     * @param  Builder<Model>  $query
     * @param  callable(Builder<Model>, string, string): void  $callback
     */
    private function applyHistoryKeyword($query, string $keyword, callable $callback): void
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return;
        }
        $like = '%'.$keyword.'%';
        $query->where(function ($q) use ($callback, $like, $keyword) {
            $callback($q, $like, $keyword);
        });
    }

    /**
     * @param  Builder<Model>  $query
     */
    private function applyDateRange($query, string $column, string $from, string $to): void
    {
        $fromDate = $this->normalizeHistoryDate($from);
        $toDate = $this->normalizeHistoryDate($to);
        if ($fromDate !== null) {
            $query->where($column, '>=', $fromDate.' 00:00:00');
        }
        if ($toDate !== null) {
            $query->where($column, '<=', $toDate.' 23:59:59');
        }
    }

    private function normalizeHistoryDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        return $value;
    }

    /**
     * @param  array<string, string>  $enumValues
     * @return list<string>
     */
    private function matchEnumKeys(array $enumValues, string $keyword): array
    {
        $keys = [];
        $lower = mb_strtolower($keyword);
        foreach ($enumValues as $key => $label) {
            if ($key === $keyword
                || mb_strpos((string) $label, $keyword) !== false
                || mb_strpos(mb_strtolower((string) $label), $lower) !== false
            ) {
                $keys[] = (string) $key;
            }
        }

        return $keys;
    }

    /**
     * 管理画面から会員を削除（ソフトデリート）。
     * スマレジ連携済みなら先にPOS会員を削除し、失敗時はローカルも削除しない。
     */
    public function delete(int $memberId, int $projectId): void
    {
        $member = $this->findForWrite($memberId, $projectId);
        $user = User::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->whereKey((int) $member->user_id)
            ->first();

        $smaregiCustomerId = trim((string) ($member->smaregi_customer_id ?? ''));
        $contractId = $this->smaregiApi->getContractIdForProject($projectId);
        $contractId = is_string($contractId) ? trim($contractId) : '';
        if ($contractId !== '' && $smaregiCustomerId !== '') {
            $result = $this->smaregiApi->deleteCustomer($contractId, $smaregiCustomerId);
            if ($result === null) {
                throw new \RuntimeException(
                    'スマレジの会員削除に失敗しました。ログ(storage/logs/laravel.log)の [Smaregi] を確認してください。'
                );
            }
        }

        $member->delete();

        if ($user !== null) {
            try {
                if (method_exists($user, 'tokens')) {
                    $user->tokens()->delete();
                }
            } catch (\Throwable $e) {
                LogHelper::warning('[AdminMember] token cleanup skipped', [
                    'user_id' => (int) $user->id,
                    'message' => $e->getMessage(),
                ]);
            }
            $this->userService->releaseLineUserIdForWithdraw($user);
            $user->delete();
        }

        LogHelper::info('[AdminMember] member deleted', [
            'project_id' => $projectId,
            'member_id' => $memberId,
            'user_id' => $user !== null ? (int) $user->id : null,
        ]);
    }
}
