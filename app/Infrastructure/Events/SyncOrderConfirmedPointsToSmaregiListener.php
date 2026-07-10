<?php

namespace App\Infrastructure\Events;

use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Shared\DomainEventDispatcher\DomainEventListener;
use App\Services\Smaregi\SmaregiPointsSyncService;

/**
 * 注文ポイントのスマレジ同期を行う。
 * OrderPointsSyncRequested のみを処理する（OrderConfirmed では同期しない。二重発火防止）。
 * used/earned はネット1回。完了 Cache は成功後のみ（部分成功・二重適用を防ぐ）。
 */
class SyncOrderConfirmedPointsToSmaregiListener implements DomainEventListener
{
    public function __construct(
        private SmaregiPointsSyncService $pointsSync,
    ) {}

    public function handle(object $event): void
    {
        if ($event instanceof OrderPointsSyncRequested === false) {
            return;
        }

        $this->pointsSync->syncOrderNetPoints(
            $event->orderId,
            $event->projectId,
            $event->userId,
        );
    }
}
