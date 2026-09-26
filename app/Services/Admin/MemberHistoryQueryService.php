<?php

namespace App\Services\Admin;

use App\Enums\MemberVisitSource;
use App\Enums\PointTransactionType;
use App\Models\CouponRedemption;
use App\Models\MemberCoupon;
use App\Models\MemberRankHistory;
use App\Models\MemberStampTransaction;
use App\Models\MemberVisit;
use App\Models\Order;
use App\Models\PointTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MemberHistoryQueryService
{
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
}
