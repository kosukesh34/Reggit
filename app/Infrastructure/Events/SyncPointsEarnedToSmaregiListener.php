<?php

namespace App\Infrastructure\Events;

use App\Domain\Member\Events\PointsEarned;
use App\Domain\Shared\DomainEventDispatcher\DomainEventListener;
use App\Services\Smaregi\SmaregiPointsSyncService;

/**
 * ポイント増減時にスマレジへ残高差分を同期する（注文以外）。
 * - ベストエフォート指定（管理画面調整）: 失敗時は warning
 * - 手動付与: 同期失敗時は例外
 * 注文由来は OrderPointsSyncRequested / OrderConfirmed 経由のネット同期を使う。
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

        if ($event->orderId !== null) {
            return;
        }

        $this->pointsSync->syncPointDelta(
            $event->memberId,
            $event->projectId,
            $event->points,
            null,
            $event->isBestEffortSync,
        );
    }
}
