<?php

namespace App\Infrastructure\Persistence\Eloquent\Coupon;

use App\Domain\Coupon\Repositories\CouponDeliveryTargetRepository;
use App\Enums\OrderStatus;
use App\Models\Member;
use App\Models\Order;

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
