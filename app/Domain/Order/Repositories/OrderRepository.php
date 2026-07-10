<?php

namespace App\Domain\Order\Repositories;

use App\Domain\Order\Aggregates\Order;
use App\Domain\Order\ValueObjects\OrderLineItem;
use App\Domain\Order\ValueObjects\PendingOrder;

interface OrderRepository
{
    public function findById(int $orderId, int $projectId): ?Order;

    public function findByIdForUpdate(int $orderId, int $projectId): ?Order;

    /** Webhook 等、テナント不明のシステム経路用。PK はグローバル一意。 */
    public function findByPrimaryKey(int $orderId): ?Order;

    public function findByPrimaryKeyForUpdate(int $orderId): ?Order;

    public function findByIdAndUserId(int $orderId, int $userId, int $projectId): ?Order;

    public function findByIdAndUserIdForUpdate(int $orderId, int $userId, int $projectId): ?Order;

    public function save(Order $order): void;

    /**
     * @param  list<OrderLineItem>  $lineItems
     */
    public function persistPending(PendingOrder $pendingOrder, array $lineItems): int;

    public function isSmaregiPointsSynced(int $orderId, int $projectId): bool;

    /** @return bool claim 取得できたとき true */
    public function tryClaimSmaregiPointsSync(int $orderId, int $projectId, int $claimTtlMinutes): bool;

    public function markSmaregiPointsSynced(int $orderId, int $projectId): void;

    public function releaseSmaregiPointsSyncClaim(int $orderId, int $projectId): void;
}
