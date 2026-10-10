<?php

namespace App\Services;

use App\Domain\Order\Events\OrderCompleted;
use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Events\OrderReady;
use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\Smaregi\SmaregiOrderTransactionOutboundService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/** The shared write boundary for admin and kitchen order state changes. */
class OrderStatusService
{
    public function __construct(
        private OrderRepository $orders,
        private OrderRewardService $rewards,
        private CouponIssueService $coupons,
        private PostCommitDomainEventDispatcher $events,
        private UnpaidOrderCancellationService $cancellation,
        private SmaregiOrderTransactionOutboundService $smaregi,
    ) {}

    public function update(int $id, string $status): void
    {
        $projectId = (int) Order::query()->findOrFail($id)->project_id;
        if ($status === OrderStatus::CANCELLED) {
            $this->cancellation->cancel($id, $projectId);

            return;
        }

        [$order, $event] = DB::transaction(function () use ($id, $status, $projectId): array {
            $aggregate = $this->orders->findByIdForUpdate($id, $projectId);
            if ($aggregate === null) {
                throw (new ModelNotFoundException)->setModel(Order::class, [$id]);
            }
            if (! $aggregate->applyStaffStatus($status)) {
                return [null, null];
            }
            $this->orders->save($aggregate);
            $order = Order::where('project_id', $projectId)->with('user.member')->findOrFail($id);
            $event = match ($status) {
                OrderStatus::CONFIRMED => new OrderConfirmed($id, $projectId, (int) $order->user_id),
                OrderStatus::READY => new OrderReady($id, $projectId, (int) $order->user_id),
                OrderStatus::COMPLETED => new OrderCompleted($id, $projectId, (int) $order->user_id),
                default => null,
            };
            if ($status === OrderStatus::COMPLETED) {
                if ($order->payment_method === PaymentMethod::CASH && $order->user_id !== null) {
                    $order->smaregi_pickup_pending = true;
                    $order->smaregi_pickup_retry_at = now();
                    $order->save();
                }
                $this->rewards->applyCashOrderLotteryIfNeeded($order);
                $member = $order->user?->member;
                if ($member !== null) {
                    // Serialize first-order benefits for the same member across orders.
                    $member->newQuery()->whereKey($member->id)->lockForUpdate()->firstOrFail();
                    $completed = Order::where('project_id', $projectId)->where('user_id', $order->user_id)
                        ->where('status', OrderStatus::COMPLETED)->lockForUpdate()->get(['id']);
                    if ($completed->count() === 1) {
                        $this->coupons->issueForFirstOrder($member);
                    }
                }
            }
            if ($event !== null) {
                $this->events->record($event);
            }

            return [$order, $event];
        }, 3);

        if ($event !== null) {
            $this->events->dispatch($event);
        }
        if ($order !== null && $status === OrderStatus::COMPLETED
            && $order->payment_method === PaymentMethod::CASH && $order->user_id !== null) {
            DB::afterCommit(function () use ($id, $projectId, $order): void {
                try {
                    $this->smaregi->finalizeCashLayawayWithRetry($id, $projectId, (int) $order->user_id);
                } catch (\Throwable $exception) {
                    // The persisted request remains available to the recovery scanner.
                    report($exception);
                }
            });
        }
    }
}
