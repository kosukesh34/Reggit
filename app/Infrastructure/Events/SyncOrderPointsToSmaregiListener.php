<?php

namespace App\Infrastructure\Events;

use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Shared\DomainEventDispatcher\DomainEventListener;
use App\Services\Smaregi\SmaregiPointsSyncService;

/**
 * 注文ポイントのスマレジ同期を行う。
 * OrderPointsSyncRequested のみを処理する（OrderConfirmed では同期しない。二重発火防止）。
 * used/earned はネット1回。完了は orders.smaregi_points_synced_at（成功後のみ）。
 * 失敗時はキューでリトライする（Cash 等の再送経路補完）。
 */
class SyncOrderPointsToSmaregiListener implements DomainEventListener
{
    public function __construct(
        private SmaregiPointsSyncService $pointsSync,
    ) {}

    public function handle(object $event): void
    {
        if ($event instanceof OrderPointsSyncRequested === false) {
            return;
        }

        $this->pointsSync->syncOrderNetPointsWithRetry(
            $event->orderId,
            $event->projectId,
            $event->userId,
        );
    }
}
