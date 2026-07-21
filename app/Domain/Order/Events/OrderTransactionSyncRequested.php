<?php

namespace App\Domain\Order\Events;

/**
 * 注文をスマレジ取引として登録するタイミングを表す。
 * Cash 等の即時決済は作成直後、Stripe は決済確定後（再送時のリトライ含む）。
 */
final class OrderTransactionSyncRequested
{
    public function __construct(
        public readonly int $orderId,
        public readonly int $projectId,
        public readonly int $userId,
    ) {}
}
