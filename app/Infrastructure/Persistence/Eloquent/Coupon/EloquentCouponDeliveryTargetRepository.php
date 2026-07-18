<?php

namespace App\Infrastructure\Persistence\Eloquent\Coupon;

use App\Domain\Coupon\Repositories\CouponDeliveryTargetRepository;
use App\Enums\OrderStatus;
use App\Models\Member;
use App\Models\Order;
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

    public function countAll(int $projectId): int
    {
        return Member::query()->where('project_id', $projectId)->count();
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
