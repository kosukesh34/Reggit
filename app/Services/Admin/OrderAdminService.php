<?php

namespace App\Services\Admin;

use App\Domain\Order\Events\OrderCompleted;
use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Events\OrderReady;
use App\Domain\Order\Exceptions\OrderOperationException;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\CouponIssueService;
use App\Services\OrderService;
use App\Services\Smaregi\SmaregiOrderTransactionOutboundService;
use App\Services\UnpaidOrderCancellationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class OrderAdminService
{
    public function __construct(
        private CouponIssueService $couponIssueService,
        private SmaregiOrderTransactionOutboundService $smaregiOrderTransactionOutbound,
        private OrderService $orderService,
        private DomainEventDispatcher $domainEvents,
        private UnpaidOrderCancellationService $cancellation,
    ) {}

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
        $order = Order::with('user')->findOrFail($id);
        if ($status === OrderStatus::CANCELLED) {
            $this->cancellation->cancel($id, (int) $order->project_id);

            return;
        }
        if ($order->status === OrderStatus::CANCELLED) {
            throw new OrderOperationException('キャンセル済みの注文は変更できません');
        }
        $previousStatus = (string) $order->status;
        $order->update(['status' => $status]);
        $this->notifyCompletedIfNeeded($order, $previousStatus);
        if ($status === OrderStatus::CONFIRMED && $previousStatus === OrderStatus::PENDING) {
            $this->domainEvents->dispatch(new OrderConfirmed(
                (int) $order->id,
                (int) $order->project_id,
                (int) ($order->user_id ?? 0),
            ));
        }
        if ($status === OrderStatus::READY && $previousStatus !== OrderStatus::READY) {
            $this->domainEvents->dispatch(new OrderReady(
                (int) $order->id,
                (int) $order->project_id,
                (int) ($order->user_id ?? 0),
            ));
        }
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
        if ($order->status === OrderStatus::CANCELLED) {
            throw new OrderOperationException('キャンセル済みの注文は変更できません');
        }
        $previousStatus = (string) $order->status;
        $order->update(['status' => OrderStatus::COMPLETED]);
        $this->notifyCompletedIfNeeded($order, $previousStatus);
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

    private function notifyCompletedIfNeeded(Order $order, string $previousStatus): void
    {
        if ($order->status === OrderStatus::COMPLETED && $previousStatus !== OrderStatus::COMPLETED) {
            $this->domainEvents->dispatch(new OrderCompleted(
                (int) $order->id,
                (int) $order->project_id,
                (int) ($order->user_id ?? 0),
            ));
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
