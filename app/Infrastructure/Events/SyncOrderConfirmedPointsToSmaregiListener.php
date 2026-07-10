<?php

namespace App\Infrastructure\Events;

use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Shared\DomainEventDispatcher\DomainEventListener;
use App\Services\Smaregi\SmaregiPointsSyncService;

/**
 * 注文ポイントのスマレジ同期を行う。
 * - OrderConfirmed: Stripe 決済確定（初回）
 * - OrderPointsSyncRequested: Cash 即時同期、および Confirm/Webhook 再送時のリトライ
 * used/earned はネット1回。完了 Cache は成功後のみ（部分成功・二重適用を防ぐ）。
 */
class SyncOrderConfirmedPointsToSmaregiListener implements DomainEventListener
{
    public function __construct(
        private SmaregiPointsSyncService $pointsSync,
    ) {}

    public function handle(object $event): void
    {
        if ($event instanceof OrderConfirmed) {
            $this->pointsSync->syncOrderNetPoints(
                $event->orderId,
                $event->projectId,
                $event->userId,
            );

            return;
        }

        if ($event instanceof OrderPointsSyncRequested) {
            $this->pointsSync->syncOrderNetPoints(
                $event->orderId,
                $event->projectId,
                $event->userId,
            );
        }
    }
}
