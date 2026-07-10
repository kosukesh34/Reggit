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
 * used/earned はネット1回で送り、完了 Cache は成功後のみ立てる（部分成功の二重適用を防ぐ）。
 * 同時実行は短い claim で抑止する（confirm + webhook の防御）。
 */
class SyncOrderConfirmedPointsToSmaregiListener implements DomainEventListener
{
    private const CACHE_KEY_PREFIX = 'smaregi_order_points_sync:';

    private const CLAIM_KEY_PREFIX = 'smaregi_order_points_sync_claim:';

    private const CACHE_TTL_DAYS = 1;

    private const CLAIM_TTL_MINUTES = 5;

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

        $doneKey = self::CACHE_KEY_PREFIX.$event->orderId;
        if (Cache::has($doneKey)) {
            return;
        }

        $claimKey = self::CLAIM_KEY_PREFIX.$event->orderId;
        if (Cache::add($claimKey, 1, now()->addMinutes(self::CLAIM_TTL_MINUTES)) === false) {
            return;
        }

        try {
            if (Cache::has($doneKey)) {
                return;
            }

            $order = $this->orderRepository->findById($event->orderId, $event->projectId);
            if ($order === null) {
                return;
            }

            $member = $this->memberRepository->findByUserId($event->userId, $event->projectId);
            if ($member === null) {
                return;
            }

            $netDelta = $order->pointsEarned() - $order->pointsUsed();
            if ($netDelta === 0) {
                Cache::put($doneKey, 1, now()->addDays(self::CACHE_TTL_DAYS));

                return;
            }

            $succeeded = $this->pointsSync->syncPointDelta(
                $member->id()->value(),
                $member->projectId(),
                $netDelta,
                $event->orderId,
            );

            if ($succeeded) {
                Cache::put($doneKey, 1, now()->addDays(self::CACHE_TTL_DAYS));
            }
        } finally {
            Cache::forget($claimKey);
        }
    }
}
