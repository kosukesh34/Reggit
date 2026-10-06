<?php

namespace App\Infrastructure\Persistence\Eloquent\Order;

use App\Domain\Order\Aggregates\Order as OrderAggregate;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Order\ValueObjects\OrderLineItem;
use App\Domain\Order\ValueObjects\PendingOrder;
use App\Enums\OrderStatus;
use App\Models\Order as OrderModel;
use App\Models\OrderItem;
use App\Models\Scopes\ProjectScope;
use Illuminate\Database\Eloquent\Builder;

class EloquentOrderRepository implements OrderRepository
{
    public function findById(int $orderId, int $projectId): ?OrderAggregate
    {
        $model = $this->newQuery()
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
        $model = $this->newQuery()
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
        $model = $this->newQuery()->whereKey($orderId)->first();
        if ($model === null) {
            return null;
        }

        return OrderMapper::toDomain($model);
    }

    public function findByPrimaryKeyForUpdate(int $orderId): ?OrderAggregate
    {
        $model = $this->newQuery()->whereKey($orderId)->lockForUpdate()->first();
        if ($model === null) {
            return null;
        }

        return OrderMapper::toDomain($model);
    }

    public function findByIdAndUserId(int $orderId, int $userId, int $projectId): ?OrderAggregate
    {
        $model = $this->newQuery()
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->first();

        if ($model === null) {
            return null;
        }

        return OrderMapper::toDomain($model);
    }

    public function findByIdAndUserIdForUpdate(int $orderId, int $userId, int $projectId): ?OrderAggregate
    {
        $model = $this->newQuery()
            ->where('id', $orderId)
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->lockForUpdate()
            ->first();

        if ($model === null) {
            return null;
        }

        return OrderMapper::toDomain($model);
    }

    public function save(OrderAggregate $order): void
    {
        $model = $this->newQuery()
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
        $model = OrderModel::withoutGlobalScope(ProjectScope::class)->create([
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
                'product_name' => $item->productName,
                'option_ids' => $item->optionIds,
                'quantity' => $item->quantity,
                'price' => $item->unitPrice,
                'notes' => $item->notes,
            ]);
        }

        return (int) $model->id;
    }

    public function isSmaregiPointsSynced(int $orderId, int $projectId): bool
    {
        return $this->newQuery()
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->whereNotNull('smaregi_points_synced_at')
            ->exists();
    }

    public function tryClaimSmaregiPointsSync(int $orderId, int $projectId, int $claimTtlMinutes): ?string
    {
        $claimExpiredBefore = now()->subMinutes($claimTtlMinutes);
        $claimedAt = now()->format('Y-m-d H:i:s');

        $affected = $this->newQuery()
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->whereNull('smaregi_points_synced_at')
            ->where(function ($query) use ($claimExpiredBefore): void {
                $query->whereNull('smaregi_points_sync_started_at')
                    ->orWhere('smaregi_points_sync_started_at', '<', $claimExpiredBefore);
            })
            ->update(['smaregi_points_sync_started_at' => $claimedAt]);

        return $affected === 1 ? $claimedAt : null;
    }

    public function markSmaregiPointsSynced(int $orderId, int $projectId): void
    {
        $this->newQuery()
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->update([
                'smaregi_points_synced_at' => now(),
                'smaregi_points_sync_started_at' => null,
            ]);
    }

    public function releaseSmaregiPointsSyncClaim(int $orderId, int $projectId, string $claimedAt): void
    {
        $this->newQuery()
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->whereNull('smaregi_points_synced_at')
            ->where('smaregi_points_sync_started_at', $claimedAt)
            ->update(['smaregi_points_sync_started_at' => null]);
    }

    public function findUnsyncedSmaregiPointsOrdersForUser(int $userId, int $projectId): array
    {
        return $this->newQuery()
            ->where('user_id', $userId)
            ->where('project_id', $projectId)
            ->whereNull('smaregi_points_synced_at')
            ->where(function ($query): void {
                $query->where('points_used', '>', 0)
                    ->orWhere('points_earned', '>', 0);
            })
            ->get(['id', 'project_id', 'user_id'])
            ->map(static fn (OrderModel $order): array => [
                'orderId' => (int) $order->id,
                'projectId' => (int) $order->project_id,
                'userId' => (int) $order->user_id,
            ])
            ->all();
    }

    public function isSmaregiTransactionSynced(int $orderId, int $projectId): bool
    {
        return $this->newQuery()
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->whereNotNull('smaregi_transaction_synced_at')
            ->exists();
    }

    public function tryClaimSmaregiTransactionSync(int $orderId, int $projectId, int $claimTtlMinutes): ?string
    {
        $claimExpiredBefore = now()->subMinutes($claimTtlMinutes);
        $claimedAt = now()->format('Y-m-d H:i:s');

        $affected = $this->newQuery()
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->whereNull('smaregi_transaction_synced_at')
            ->where(function ($query) use ($claimExpiredBefore): void {
                $query->whereNull('smaregi_transaction_sync_started_at')
                    ->orWhere('smaregi_transaction_sync_started_at', '<', $claimExpiredBefore);
            })
            ->update(['smaregi_transaction_sync_started_at' => $claimedAt]);

        return $affected === 1 ? $claimedAt : null;
    }

    public function markSmaregiTransactionSynced(
        int $orderId,
        int $projectId,
        string $smaregiTransactionHeadId,
    ): void {
        $this->newQuery()
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->update([
                'smaregi_transaction_head_id' => $smaregiTransactionHeadId,
                'smaregi_transaction_synced_at' => now(),
                'smaregi_transaction_sync_started_at' => null,
            ]);
    }

    public function releaseSmaregiTransactionSyncClaim(int $orderId, int $projectId, string $claimedAt): void
    {
        $this->newQuery()
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->whereNull('smaregi_transaction_synced_at')
            ->where('smaregi_transaction_sync_started_at', $claimedAt)
            ->update(['smaregi_transaction_sync_started_at' => null]);
    }

    public function existsBySmaregiTransactionHeadId(int $projectId, string $smaregiTransactionHeadId): bool
    {
        $headId = trim($smaregiTransactionHeadId);
        if ($headId === '') {
            return false;
        }

        return $this->newQuery()
            ->where('project_id', $projectId)
            ->where('smaregi_transaction_head_id', $headId)
            ->exists();
    }

    public function findIdBySmaregiTransactionHeadId(int $projectId, string $smaregiTransactionHeadId): ?int
    {
        $headId = trim($smaregiTransactionHeadId);
        if ($headId === '') {
            return null;
        }

        $id = $this->newQuery()
            ->where('project_id', $projectId)
            ->where('smaregi_transaction_head_id', $headId)
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    public function hasSmaregiLayaway(int $orderId, int $projectId): bool
    {
        return $this->newQuery()
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->whereNotNull('smaregi_layaway_head_id')
            ->where('smaregi_layaway_head_id', '!=', '')
            ->exists();
    }

    public function markSmaregiLayawayRegistered(
        int $orderId,
        int $projectId,
        string $smaregiLayawayHeadId,
    ): void {
        $this->newQuery()
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->update([
                'smaregi_layaway_head_id' => $smaregiLayawayHeadId,
                'smaregi_transaction_sync_started_at' => null,
            ]);
    }

    public function findIdBySmaregiLayawayHeadId(int $projectId, string $smaregiLayawayHeadId): ?int
    {
        $headId = trim($smaregiLayawayHeadId);
        if ($headId === '') {
            return null;
        }

        $id = $this->newQuery()
            ->where('project_id', $projectId)
            ->where('smaregi_layaway_head_id', $headId)
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function newQuery(): Builder
    {
        return OrderModel::query()->withoutGlobalScope(ProjectScope::class);
    }
}
