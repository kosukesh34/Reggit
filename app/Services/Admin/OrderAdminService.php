<?php

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\CouponIssueService;
use App\Services\OrderService;
use App\Services\Smaregi\SmaregiOrderTransactionOutboundService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderAdminService
{
    public function __construct(
        private CouponIssueService $couponIssueService,
        private SmaregiOrderTransactionOutboundService $smaregiOrderTransactionOutbound,
        private OrderService $orderService,
    ) {}

    /** 決済済み = 確定・準備中・受け取り可能・完了 */
    private const PAID_STATUSES = [
        OrderStatus::CONFIRMED,
        OrderStatus::PREPARING,
        OrderStatus::READY,
        OrderStatus::COMPLETED,
    ];

    public function getPaginatedList(?string $status, int $perPage = 20): LengthAwarePaginator
    {
        $query = Order::with('user.member')
            ->orderByDesc('created_at');
        if ($status && $status !== 'all') {
            $query->where('status', $status);
        } else {
            $query->whereIn('status', self::PAID_STATUSES);
        }

        return $query->paginate($perPage);
    }

    public function getStatusCounts(): array
    {
        $statuses = array_keys(OrderStatus::getValues());
        $counts = [
            'all' => Order::whereIn('status', self::PAID_STATUSES)->count(),
            'paid' => Order::whereIn('status', self::PAID_STATUSES)->count(),
        ];
        foreach ($statuses as $status) {
            $counts[$status] = Order::where('status', $status)->count();
        }

        return $counts;
    }

    public function findOrFail(int $id): Order
    {
        return Order::with(['items.product', 'user.member'])->findOrFail($id);
    }

    public function updateStatus(int $id, string $status): void
    {
        $order = Order::with('user')->findOrFail($id);
        Order::whereKey($id)->update(['status' => $status]);
        if ($status === OrderStatus::COMPLETED) {
            $completed = $order->fresh();
            $this->finalizeCashLayawayIfNeeded($completed);
            if ($completed !== null) {
                $this->orderService->applyCashOrderLotteryIfNeeded($completed);
            }
        }
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
        $completed = $order->fresh();
        $this->finalizeCashLayawayIfNeeded($completed);
        if ($completed !== null) {
            $this->orderService->applyCashOrderLotteryIfNeeded($completed);
        }
    }

    private function finalizeCashLayawayIfNeeded(?Order $order): void
    {
        if ($order === null) {
            return;
        }
        if ($order->payment_method !== PaymentMethod::CASH) {
            return;
        }
        if ($order->user_id === null) {
            return;
        }

        $this->smaregiOrderTransactionOutbound->finalizeCashLayawayWithRetry(
            (int) $order->id,
            (int) $order->project_id,
            (int) $order->user_id,
        );
    }
}
