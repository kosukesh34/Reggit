<?php

namespace App\Services\Admin;

use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use App\Enums\OrderStatus;

class OrderAdminService
{
    public function getPaginatedList(?string $status, int $perPage = 20): LengthAwarePaginator
    {
        $query = Order::with('user')
            ->orderByDesc('created_at');
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }
        return $query->paginate($perPage);
    }

    public function getStatusCounts(): array
    {
        $statuses = array_keys(OrderStatus::getValues());
        $counts = ['all' => Order::count()];
        foreach ($statuses as $status) {
            $counts[$status] = Order::where('status', $status)->count();
        }
        return $counts;
    }

    public function findOrFail(int $id): Order
    {
        return Order::with(['items.product', 'user'])->findOrFail($id);
    }

    public function updateStatus(int $id, string $status): void
    {
        Order::whereKey($id)->update(['status' => $status]);
    }

    public function complete(int $id): void
    {
        Order::whereKey($id)->update(['status' => OrderStatus::COMPLETED]);
    }
}
