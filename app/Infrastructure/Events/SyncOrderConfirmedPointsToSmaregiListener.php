<?php

namespace App\Infrastructure\Events;

use App\Domain\Member\Events\PointsEarned;
use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventListener;

/**
 * Stripe 決済確定後に、注文で変動したポイント差分をスマレジへ同期する。
 * 未決済作成時点では同期せず、確定時にまとめて送る。
 */
class SyncOrderConfirmedPointsToSmaregiListener implements DomainEventListener
{
    public function __construct(
        private OrderRepository $orderRepository,
        private MemberRepository $memberRepository,
        private SyncPointsEarnedToSmaregiListener $pointsSync,
    ) {}

    public function handle(object $event): void
    {
        if ($event instanceof OrderConfirmed === false) {
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

        $memberId = $member->id()->value();
        $projectId = $member->projectId();
        $orderId = $event->orderId;

        if ($order->pointsUsed() > 0) {
            $this->pointsSync->handle(new PointsEarned(
                $memberId,
                $projectId,
                -$order->pointsUsed(),
                $orderId,
            ));
        }

        if ($order->pointsEarned() > 0) {
            $this->pointsSync->handle(new PointsEarned(
                $memberId,
                $projectId,
                $order->pointsEarned(),
                $orderId,
            ));
        }
    }
}
