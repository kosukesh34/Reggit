<?php

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\CouponIssueService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderAdminService
{
    public function __construct(
        private ?CouponIssueService $couponIssueService = null
    ) {
        $this->couponIssueService = $couponIssueService ?? app(CouponIssueService::class);
    }

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
        $order = Order::with('user.member')->findOrFail($id);
        Order::whereKey($id)->update(['status' => OrderStatus::COMPLETED]);
        $member = $order->user?->member;
        if ($member !== null) {
            $completedCount = Order::where('user_id', $order->user_id)->where('status', OrderStatus::COMPLETED)->count();
            if ($completedCount === 1) {
                $this->couponIssueService->issueForFirstOrder($member);
            }
        }
    }
}
