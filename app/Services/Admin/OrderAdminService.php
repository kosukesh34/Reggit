<?php

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\OrderStatusService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderAdminService
{
    public function __construct(private OrderStatusService $orderStatus) {}

    /** 決済済み = 確定・準備中・受け取り可能・完了 */
    private const PAID_STATUSES = [
        OrderStatus::CONFIRMED,
        OrderStatus::PREPARING,
        OrderStatus::READY,
        OrderStatus::COMPLETED,
    ];

    public function getPaginatedList(?string $status, int $perPage = 20, ?int $userId = null): LengthAwarePaginator
    {
        $query = Order::with('user.member')
            ->orderByDesc('created_at');
        if ($userId !== null) {
            $query->where('user_id', $userId);
        }
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', self::PAID_STATUSES);
        }

        return $query->paginate($perPage);
    }

    public function getStatusCounts(?int $userId = null): array
    {
        $base = Order::query();
        if ($userId !== null) {
            $base->where('user_id', $userId);
        }
        $statuses = array_keys(OrderStatus::getValues());
        $counts = [
            'all' => (clone $base)->whereIn('status', self::PAID_STATUSES)->count(),
            'paid' => (clone $base)->whereIn('status', self::PAID_STATUSES)->count(),
        ];
        foreach ($statuses as $status) {
            $counts[$status] = (clone $base)->where('status', $status)->count();
        }

        return $counts;
    }

    public function findOrFail(int $id): Order
    {
        return Order::with(['items.product', 'user.member'])->findOrFail($id);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->orderStatus->update($id, $status);
    }

    public function complete(int $id): void
    {
        $this->orderStatus->update($id, OrderStatus::COMPLETED);
    }
}
