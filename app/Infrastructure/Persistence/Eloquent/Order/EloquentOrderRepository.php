<?php

namespace App\Infrastructure\Persistence\Eloquent\Order;

use App\Domain\Order\Aggregates\Order as OrderAggregate;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Order\ValueObjects\OrderLineItem;
use App\Domain\Order\ValueObjects\PendingOrder;
use App\Enums\OrderStatus;
use App\Models\Order as OrderModel;
use App\Models\OrderItem;

class EloquentOrderRepository implements OrderRepository
{
    public function findById(int $orderId, int $projectId): ?OrderAggregate
    {
        $model = OrderModel::query()
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->first();
        if ($model === null) {
            return null;
        }

        return OrderMapper::toDomain($model);
    }

    public function findByIdForUpdate(int $orderId, int $projectId): ?OrderAggregate
    {
        $model = OrderModel::query()
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->lockForUpdate()
            ->first();
        if ($model === null) {
            return null;
        }

        return OrderMapper::toDomain($model);
    }

    public function findByPrimaryKey(int $orderId): ?OrderAggregate
    {
        $model = OrderModel::query()->whereKey($orderId)->first();
        if ($model === null) {
            return null;
        }

        return OrderMapper::toDomain($model);
    }

    public function findByPrimaryKeyForUpdate(int $orderId): ?OrderAggregate
    {
        $model = OrderModel::query()->whereKey($orderId)->lockForUpdate()->first();
        if ($model === null) {
            return null;
        }

        return OrderMapper::toDomain($model);
    }

    public function findByIdAndUserId(int $orderId, int $userId, int $projectId): ?OrderAggregate
    {
        $model = OrderModel::query()
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->first();

        if ($model === null) {
            return null;
        }

        return OrderMapper::toDomain($model);
    }

    public function save(OrderAggregate $order): void
    {
        $model = OrderModel::query()
            ->whereKey($order->id()->value())
            ->where('project_id', $order->projectId())
            ->first();
        if ($model === null) {
            throw new \RuntimeException('Order not found for save: '.$order->id()->value());
        }

        $model->update([
            'status' => $order->status(),
            'payment_method' => $order->paymentMethod(),
        ]);
    }

    public function persistPending(PendingOrder $pendingOrder, array $lineItems): int
    {
        $model = OrderModel::create([
            'project_id' => $pendingOrder->projectId,
            'user_id' => $pendingOrder->userId,
            'total_amount' => $pendingOrder->totalAmount,
            'status' => OrderStatus::PENDING,
            'payment_method' => $pendingOrder->paymentMethod,
            'points_used' => $pendingOrder->pointsUsed,
            'points_earned' => 0,
            'notes' => $pendingOrder->notes,
            'order_type' => $pendingOrder->orderType,
        ]);

        foreach ($lineItems as $item) {
            if ($item instanceof OrderLineItem === false) {
                throw new \InvalidArgumentException('lineItems must contain OrderLineItem instances.');
            }

            OrderItem::create([
                'order_id' => $model->id,
                'product_id' => $item->productId,
                'quantity' => $item->quantity,
                'price' => $item->unitPrice,
                'notes' => $item->notes,
            ]);
        }

        return (int) $model->id;
    }
}
