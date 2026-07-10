<?php

namespace App\Domain\Order\Repositories;

use App\Domain\Order\Aggregates\Order;
use App\Domain\Order\ValueObjects\OrderLineItem;
use App\Domain\Order\ValueObjects\PendingOrder;

interface OrderRepository
{
    public function findById(int $orderId, int $projectId): ?Order;

    /** Webhook 等、テナント不明のシステム経路用。PK はグローバル一意。 */
    public function findByPrimaryKey(int $orderId): ?Order;

    public function findByIdAndUserId(int $orderId, int $userId, int $projectId): ?Order;

    public function save(Order $order): void;

    /**
     * @param  list<OrderLineItem>  $lineItems
     */
    public function persistPending(PendingOrder $pendingOrder, array $lineItems): int;
}
