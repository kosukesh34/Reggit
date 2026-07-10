<?php

namespace App\Infrastructure\Events;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventListener;
use App\Services\Smaregi\SmaregiPointsSyncService;
use Illuminate\Support\Facades\Cache;

/**
 * Stripe 決済確定後に、注文で変動したポイント差分をスマレジへ同期する。
 * 未決済作成時点では同期せず、確定時にまとめて送る。
 * 同一 order への再配信は Cache で抑止する（confirm + webhook の二重発火対策）。
 */
class SyncOrderConfirmedPointsToSmaregiListener implements DomainEventListener
{
    private const CACHE_KEY_PREFIX = 'smaregi_order_points_sync:';

    private const CACHE_TTL_DAYS = 1;

    public function __construct(
        private OrderRepository $orderRepository,
        private MemberRepository $memberRepository,
        private SmaregiPointsSyncService $pointsSync,
    ) {}

    public function handle(object $event): void
    {
        if ($event instanceof OrderConfirmed === false) {
            return;
        }

        $cacheKey = self::CACHE_KEY_PREFIX.$event->orderId;
        if (Cache::add($cacheKey, 1, now()->addDays(self::CACHE_TTL_DAYS)) === false) {
            return;
        }

        $order = $this->orderRepository->findById($event->orderId, $event->projectId);
        if ($order === null) {
            Cache::forget($cacheKey);

            return;
        }

        $member = $this->memberRepository->findByUserId($event->userId, $event->projectId);
        if ($member === null) {
            Cache::forget($cacheKey);

            return;
        }

        $memberId = $member->id()->value();
        $projectId = $member->projectId();
        $orderId = $event->orderId;
        $allSucceeded = true;

        if ($order->pointsUsed() > 0) {
            $allSucceeded = $this->pointsSync->syncPointDelta(
                $memberId,
                $projectId,
                -$order->pointsUsed(),
                $orderId,
            ) && $allSucceeded;
        }

        if ($order->pointsEarned() > 0) {
            $allSucceeded = $this->pointsSync->syncPointDelta(
                $memberId,
                $projectId,
                $order->pointsEarned(),
                $orderId,
            ) && $allSucceeded;
        }

        if ($allSucceeded === false) {
            Cache::forget($cacheKey);
        }
    }
}
