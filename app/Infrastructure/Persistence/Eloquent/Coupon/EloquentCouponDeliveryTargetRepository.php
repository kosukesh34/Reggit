<?php

namespace App\Infrastructure\Persistence\Eloquent\Coupon;

use App\Domain\Coupon\Repositories\CouponDeliveryTargetRepository;
use App\Enums\OrderStatus;
use App\Models\Member;
use App\Models\MemberStamp;
use App\Models\Order;
use App\Models\Product;
use App\Support\MemberChannel;
use Carbon\Carbon;

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
            ->where('status', '!=', OrderStatus::CANCELLED)
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
            ->where('status', '!=', OrderStatus::CANCELLED)
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
            ->where('status', '!=', OrderStatus::CANCELLED)
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

    public function findIdsByLastVisited(int $projectId, string $mode, int $days): array
    {
        $days = max(1, $days);
        $boundary = Carbon::today()->subDays($days)->startOfDay();

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
            $targetDate = Carbon::today()->subDays($days)->format('Y-m-d');
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
                    ->where('status', '!=', OrderStatus::CANCELLED)
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

    public function findIdsByProfileField(int $projectId, string $key, array $values): array
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
            ->filter(function (Member $member) use ($key, $expected): bool {
                $actual = data_get($member->profile_extra, $key);
                $actualValues = is_array($actual) ? $actual : [$actual];

                return collect($actualValues)
                    ->map(fn ($value): string => trim((string) $value))
                    ->intersect($expected)
                    ->isNotEmpty();
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
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
