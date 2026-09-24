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
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use App\Services\Smaregi\SmaregiCustomerUpdatePayloadBuilder;
use App\Services\Stamp\StampBalanceService;
use App\Services\UserService;
use App\Util\DateUtil;
use App\Util\LogHelper;
use App\Util\StrHelper;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MemberAdminService
{
    public function __construct(
        private SmaregiApiService $smaregiApi,
        private SmaregiCustomerUpdatePayloadBuilder $smaregiCustomerUpdatePayloadBuilder,
        private SmaregiCustomerRankAssignmentService $smaregiCustomerRankAssignmentService,
        private MemberRankCalculator $rankCalculator,
        private MemberPointsExpiryCalculator $expiryCalculator,
        private MemberCouponRepository $memberCouponRepository,
        private ShopSettingRepository $shopSettingRepository,
        private UserService $userService,
        private StampBalanceService $stampBalanceService,
    ) {}

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
        $rankInfo = $this->rankCalculator->getRankInfo($member);
        $member->setAttribute('rank_label', $rankInfo['current_rank_label'] ?? $member->rank);
        $member->setAttribute('points_expiry', $this->expiryCalculator->getExpiryInfo($member));
        $member->setAttribute('visits_count', $member->visits()->count());

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
     *     recent_orders: Collection,
     *     order_list_total: int
     * }
     */
    public function getPurchaseSummary(Member $member): array
    {
        $userId = $member->user_id;
        $orderRevenue = 0.0;
        $orderCount = 0;
        $lastOrderAt = null;
        $recentOrders = collect();
        $orderListTotal = 0;

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
                ->limit(self::HISTORY_PER_PAGE)
                ->get();
            $orderListTotal = Order::where('user_id', $userId)->count();
        }

        $posVisits = MemberVisit::query()
            ->where('member_id', $member->id)
            ->where('source', MemberVisitSource::SMAREGI)
            ->whereNull('order_id')
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
            'order_list_total' => $orderListTotal,
        ];
    }

    /** 配布用の有効クーポン一覧（同一プロジェクト） */
    public function getActiveCouponsForSelect(int $projectId): Collection
    {
        return Coupon::where('project_id', $projectId)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('end_at')->orWhere('end_at', '>=', now());
            })
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
    public function issueCouponToMember(
        Member $member,
        int $couponId,
        ?\DateTimeInterface $validFrom = null,
        ?\DateTimeInterface $validUntil = null,
        ?string $issueSourceType = null,
        ?string $issueSourceId = null,
        ?string $issueGrantKey = null,
    ): MemberCoupon {
        $coupon = Coupon::where('project_id', $member->project_id)->findOrFail($couponId);
        $now = now();
        if ($coupon->end_at !== null && $coupon->end_at->lt($now)) {
            throw new \InvalidArgumentException('このクーポンは期限切れです');
        }
        $validFrom = $validFrom ?? $coupon->start_at ?? $now;
        $validUntil = $validUntil ?? $coupon->end_at;

        $this->memberCouponRepository->issue(new PendingIssuedCoupon(
            (int) $member->project_id,
            (int) $member->id,
            (int) $coupon->id,
            (string) $coupon->name,
            $now,
            $validFrom,
            $validUntil instanceof \DateTimeInterface ? $validUntil : null,
            $issueSourceType,
            $issueSourceId,
            $issueGrantKey,
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
    public function issueCouponToMembers(
        int $projectId,
        int $couponId,
        array $memberIds = [],
        ?string $issueSourceType = null,
        ?string $issueSourceId = null,
        bool $shouldNotify = true,
    ): array {
        $coupon = Coupon::where('project_id', $projectId)->findOrFail($couponId);
        $query = Member::where('project_id', $projectId);
        if ($memberIds !== []) {
            $query->whereIn('id', $memberIds);
        }
        $members = $query->get();
        if ($coupon->end_at !== null && $coupon->end_at->lt(now())) {
            throw new \InvalidArgumentException('このクーポンは期限切れです');
        }
        $validUntil = $coupon->end_at;
        $now = now();
        $issuedMemberIds = [];
        foreach ($members as $member) {
            $grantKey = null;
            if ($issueSourceType !== null && $issueSourceId !== null) {
                $grantKey = implode(':', [$issueSourceType, $issueSourceId, $couponId, (int) $member->id]);
                if ($this->memberCouponRepository->hasIssuedGrantKey((int) $member->id, $grantKey, $projectId)) {
                    continue;
                }
            } elseif ($this->memberCouponRepository->hasUnusedValid(
                (int) $member->id,
                $couponId,
                $now,
                $projectId,
            )) {
                continue;
            }
            $issue = function () use (
                $member,
                $couponId,
                $validUntil,
                $issueSourceType,
                $issueSourceId,
                $grantKey,
            ): void {
                $this->issueCouponToMember(
                    $member,
                    $couponId,
                    null,
                    $validUntil,
                    $issueSourceType,
                    $issueSourceId,
                    $grantKey,
                );
            };
            if ($shouldNotify) {
                $issue();
            } else {
                MemberCoupon::withoutEvents($issue);
            }
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
        ?int $orderId = null,
        ?string $issueSourceType = null,
        ?string $issueSourceId = null,
    ): MemberStamp {
        if ($count < 1) {
            throw new \InvalidArgumentException('スタンプ数は1以上を指定してください');
        }

        return $this->adjustStampsToMember(
            $member,
            $stampCardId,
            $count,
            $description ?? 'スタンプ付与',
            $orderId,
            $issueSourceType,
            $issueSourceId,
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
        ?int $orderId = null,
        ?string $issueSourceType = null,
        ?string $issueSourceId = null,
    ): MemberStamp {
        if ($delta === 0) {
            throw new \InvalidArgumentException('スタンプ数は0以外を指定してください');
        }

        $stampCard = StampCard::where('project_id', $member->project_id)->findOrFail($stampCardId);

        return $this->stampBalanceService->adjust(
            $member,
            $stampCard,
            $delta,
            $description,
            $orderId,
            [],
            $issueSourceType,
            $issueSourceId,
        );
    }

    /**
     * 来店を記録し、最終来店日時を更新する。
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
        $visitedCarbon = DateUtil::nullableCarbon($visitedAt);
        if ($visitedCarbon === null) {
            throw new \InvalidArgumentException('来店日時が不正です');
        }
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

        $currentLast = $member->last_visited_at;
        if ($currentLast === null || $visitedCarbon->gt($currentLast)) {
            $member->update(['last_visited_at' => $visitedCarbon]);
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

    /** @return Collection<int, int> */
    public function getIdsByProject(int $projectId): Collection
    {
        return Member::where('project_id', $projectId)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();
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
            'postal_code', 'prefecture', 'city', 'street', 'sex', 'mail_receive_flag', 'notification_preference', 'profile_extra',
        ];
        $updateMember = array_intersect_key($memberData, array_flip($allowedMember));
        $updateMember = array_filter(
            $updateMember,
            static fn ($value, string $key): bool => $key === 'profile_extra' || $key === 'mail_receive_flag' || $value !== null,
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
                    $this->smaregiCustomerRankAssignmentService->assignRankToCustomer($member, $newRank);
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
        $customFieldDefs = $this->shopSettingRepository->getProfileFieldSettings((int) $member->project_id)['custom_fields'] ?? [];
        $body = $this->smaregiCustomerUpdatePayloadBuilder->buildFromAdminMemberData($mergedUser, array_merge(
            $member->only([
                'member_number', 'birthday', 'address', 'postal_code', 'prefecture', 'city', 'street', 'sex', 'mail_receive_flag',
            ]),
            ['profile_extra' => $member->profile_extra],
        ), $customFieldDefs);
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

    public const HISTORY_PER_PAGE = 10;

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
                ->orWhereHas('order', function ($oq) use ($like, $keyword): void {
                    $oq->where('order_number', 'like', $like);
                    if (ctype_digit($keyword)) {
                        $oq->orWhere('id', (int) $keyword);
                    }
                });
            if ($typeKeys !== []) {
                $q->orWhereIn('type', $typeKeys);
            }
        });
        $this->applyDateRange($query, 'created_at', $from, $to);

        $paginator = $query->paginate($perPage, ['*'], $pageName);
        $this->attachOrdersFromSmaregiDescriptions($paginator->getCollection());

        return $paginator;
    }

    /**
     * 過去データで order_id 未設定のスマレジ取引付与を、description の headId から注文へ紐づける。
     *
     * @param  Collection<int, PointTransaction>  $transactions
     */
    private function attachOrdersFromSmaregiDescriptions($transactions): void
    {
        $headIdsByProject = [];
        foreach ($transactions as $transaction) {
            if ($transaction->order !== null || $transaction->order_id !== null) {
                continue;
            }
            $headId = $this->extractSmaregiHeadIdFromDescription((string) ($transaction->description ?? ''));
            if ($headId === null) {
                continue;
            }
            $projectId = (int) $transaction->project_id;
            $headIdsByProject[$projectId][$headId] = true;
        }

        if ($headIdsByProject === []) {
            return;
        }

        $ordersByKey = [];
        foreach ($headIdsByProject as $projectId => $headIdMap) {
            $orders = Order::query()
                ->where('project_id', $projectId)
                ->whereIn('smaregi_transaction_head_id', array_keys($headIdMap))
                ->get();
            foreach ($orders as $order) {
                $ordersByKey[$projectId.'|'.(string) $order->smaregi_transaction_head_id] = $order;
            }
        }

        foreach ($transactions as $transaction) {
            if ($transaction->order !== null) {
                continue;
            }
            $headId = $this->extractSmaregiHeadIdFromDescription((string) ($transaction->description ?? ''));
            if ($headId === null) {
                continue;
            }
            $order = $ordersByKey[(int) $transaction->project_id.'|'.$headId] ?? null;
            if ($order === null) {
                continue;
            }
            $transaction->setRelation('order', $order);
        }
    }

    private function extractSmaregiHeadIdFromDescription(string $description): ?string
    {
        if (preg_match('/#([A-Za-z0-9_-]+)\s*$/u', $description, $matches) !== 1) {
            return null;
        }
        $headId = trim($matches[1]);

        return $headId !== '' ? $headId : null;
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
