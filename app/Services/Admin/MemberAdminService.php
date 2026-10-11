<?php

namespace App\Services\Admin;

use App\Domain\Settings\Repositories\MemberSettings;
use App\Enums\MemberVisitSource;
use App\Enums\OrderStatus;
use App\Models\Member;
use App\Models\MemberRankHistory;
use App\Models\MemberVisit;
use App\Models\Order;
use App\Models\Scopes\ProjectScope;
use App\Models\User;
use App\Services\CustomerStoreProfile;
use App\Services\MemberPointsExpiryCalculator;
use App\Services\MemberRankCalculator;
use App\Services\MemberVisitRecorder;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use App\Services\Smaregi\SmaregiCustomerUpdatePayloadBuilder;
use App\Services\UserService;
use App\Support\AdminMemberIdentifier;
use App\Util\LogHelper;
use App\Util\StrHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MemberAdminService
{
    private const RECENT_ORDER_LIMIT = 10;

    public function __construct(
        private SmaregiApiService $smaregiApi,
        private SmaregiCustomerUpdatePayloadBuilder $smaregiCustomerUpdatePayloadBuilder,
        private CustomerStoreProfile $customerStoreProfile,
        private SmaregiCustomerRankAssignmentService $smaregiCustomerRankAssignmentService,
        private MemberRankCalculator $rankCalculator,
        private MemberPointsExpiryCalculator $expiryCalculator,
        private MemberSettings $shopSettingRepository,
        private UserService $userService,
        private MemberVisitRecorder $memberVisitRecorder,
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
                ->limit(self::RECENT_ORDER_LIMIT)
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
        return $this->memberVisitRecorder->record(
            $member, $visitedAt, $source, $orderId, $note, $amount, $smaregiTransactionHeadId,
        );
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
            $query->where(function ($inner) use ($keyword, $projectId) {
                $inner->where(AdminMemberIdentifier::usesId($projectId) ? 'id' : 'member_number', 'like', '%'.$keyword.'%')
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
                'display_identifier' => AdminMemberIdentifier::value($member),
                'member_number' => (string) ($member->member_number ?? ''),
                'name' => (string) ($member->user?->name ?? ''),
            ])
            ->values();
    }

    /**
     * @param  list<string>  $numbers
     * @return Collection<int, array{id: int, member_number: string, name: string}>
     */
    public function findByMemberNumbers(int $projectId, array $numbers): Collection
    {
        $normalized = collect($numbers)
            ->map(fn ($number): string => trim((string) $number))
            ->filter(fn (string $number): bool => $number !== '')
            ->unique()
            ->take(500)
            ->values();
        if ($normalized->isEmpty()) {
            return collect();
        }

        return Member::query()
            ->where('project_id', $projectId)
            ->whereIn(AdminMemberIdentifier::usesId($projectId) ? 'id' : 'member_number', $normalized->all())
            ->with(['user:id,name'])
            ->get()
            ->map(fn (Member $member) => [
                'id' => (int) $member->id,
                'display_identifier' => AdminMemberIdentifier::value($member),
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

        $allowedMember = [
            'member_number', 'status', 'rank', 'birthday', 'address',
            'postal_code', 'prefecture', 'city', 'street', 'sex', 'mail_receive_flag', 'customer_store_id', 'notification_preference', 'profile_extra',
        ];
        $updateMember = array_intersect_key($memberData, array_flip($allowedMember));
        $updateMember = array_filter(
            $updateMember,
            static fn ($value, string $key): bool => $key === 'profile_extra' || $key === 'mail_receive_flag' || $key === 'customer_store_id' || $value !== null,
            ARRAY_FILTER_USE_BOTH
        );
        DB::transaction(function () use ($member, $updateUser, $updateMember): void {
            $expectedUserId = (int) $member->user_id;
            $user = User::query()->whereKey($expectedUserId)->where('project_id', $member->project_id)
                ->lockForUpdate()->first();
            $member = Member::query()->where('project_id', $member->project_id)
                ->lockForUpdate()->findOrFail($member->id);
            if ((int) $member->user_id !== $expectedUserId) {
                throw new \RuntimeException('会員情報が変更されました。再読み込みしてから保存してください。');
            }
            if ($user !== null && $updateUser !== []) {
                $user->update($updateUser);
            }
            if ($updateMember === []) {
                return;
            }
            $previousRank = (string) ($member->rank ?? '');
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
        });
        $member->refresh();
        $user?->refresh();

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
        $body = $this->smaregiCustomerUpdatePayloadBuilder->buildFromAdminMemberData($mergedUser, $this->customerStoreProfile->forSmaregi(array_merge(
            $member->only([
                'member_number', 'birthday', 'address', 'postal_code', 'prefecture', 'city', 'street', 'sex', 'mail_receive_flag', 'customer_store_id',
            ]),
            ['profile_extra' => $member->profile_extra],
        ), (int) $member->project_id), $customFieldDefs);
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

    /**
     * 管理画面から会員を削除（ソフトデリート）。
     * スマレジ連携済みなら先にPOS会員を削除し、失敗時はローカルも削除しない。
     */
    public function delete(int $memberId, int $projectId): void
    {
        $member = $this->findForWrite($memberId, $projectId);
        $expectedUserId = (int) $member->user_id;

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

        $user = DB::transaction(function () use ($memberId, $projectId, $expectedUserId, $smaregiCustomerId): ?User {
            $user = User::query()->withoutGlobalScope(ProjectScope::class)
                ->whereKey($expectedUserId)->where('project_id', $projectId)->lockForUpdate()->first();
            $member = Member::query()->withoutGlobalScope(ProjectScope::class)
                ->where('project_id', $projectId)->lockForUpdate()->findOrFail($memberId);
            if ((int) $member->user_id !== $expectedUserId
                || trim((string) $member->smaregi_customer_id) !== $smaregiCustomerId) {
                throw new \RuntimeException('会員の連携情報が変更されました。再読み込みしてから削除してください。');
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

            return $user;
        });

        LogHelper::info('[AdminMember] member deleted', [
            'project_id' => $projectId,
            'member_id' => $memberId,
            'user_id' => $user !== null ? (int) $user->id : null,
        ]);
    }
}
