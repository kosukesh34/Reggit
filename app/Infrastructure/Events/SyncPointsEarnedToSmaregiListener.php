<?php

namespace App\Infrastructure\Events;

use App\Domain\Member\Events\PointsEarned;
use App\Domain\Shared\DomainEventDispatcher\DomainEventListener;
use App\Services\Smaregi\SmaregiPointsSyncService;

/**
 * ポイント増減時にスマレジへ残高差分を同期する。
 * - 注文由来（orderId あり・現金等の即時決済、または決済確定後）: 同期する。失敗時は warning
 * - ベストエフォート指定（管理画面調整）: 同期する。失敗時は warning
 * - それ以外の手動付与: 同期失敗時は例外
 * Stripe 未決済の注文作成時はイベントを発行しない（確定後に SyncOrderConfirmedPointsToSmaregiListener）。
 * points は加算が正、取り消し・減算が負。
 */
class SyncPointsEarnedToSmaregiListener implements DomainEventListener
{
    public function __construct(
        private SmaregiPointsSyncService $pointsSync,
    ) {}

    public function handle(object $event): void
    {
        if ($event instanceof PointsEarned === false) {
            return;
        }

        $this->pointsSync->syncPointDelta(
            $event->memberId,
            $event->projectId,
            $event->points,
            $event->orderId,
            $event->isBestEffortSync,
        );
    }
}
