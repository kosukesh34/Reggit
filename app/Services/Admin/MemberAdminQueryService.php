<?php

namespace App\Services\Admin;

use App\Domain\Settings\Repositories\MemberSettings;
use App\Enums\MemberVisitSource;
use App\Enums\OrderStatus;
use App\Models\Member;
use App\Models\MemberVisit;
use App\Models\Order;
use App\Services\MemberPointsExpiryCalculator;
use App\Services\MemberRankCalculator;
use App\Support\AdminMemberIdentifier;
use App\Util\StrHelper;
use Illuminate\Support\Collection;

/** 管理画面での会員の参照（詳細表示・購買集計・検索・配信対象の会員ID）。 */
class MemberAdminQueryService
{
    private const RECENT_ORDER_LIMIT = 10;

    public function __construct(
        private MemberRankCalculator $rankCalculator,
        private MemberPointsExpiryCalculator $expiryCalculator,
        private MemberSettings $shopSettingRepository,
        private AdminMemberIdentifier $memberIdentifier,
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
                $inner->where($this->memberIdentifier->usesId($projectId) ? 'id' : 'member_number', 'like', '%'.$keyword.'%')
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
                'display_identifier' => $this->memberIdentifier->value($member),
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
            ->whereIn($this->memberIdentifier->usesId($projectId) ? 'id' : 'member_number', $normalized->all())
            ->with(['user:id,name'])
            ->get()
            ->map(fn (Member $member) => [
                'id' => (int) $member->id,
                'display_identifier' => $this->memberIdentifier->value($member),
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
}
