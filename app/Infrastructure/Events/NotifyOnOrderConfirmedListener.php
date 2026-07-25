<?php

namespace App\Infrastructure\Events;

use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Shared\DomainEventDispatcher\DomainEventListener;
use App\Services\Kitchen\KitchenPushService;
use App\Services\OrderNotificationService;
use App\Util\LogHelper;

class NotifyOnOrderConfirmedListener implements DomainEventListener
{
    public function __construct(
        private OrderNotificationService $orderNotificationService,
        private KitchenPushService $kitchenPushService,
    ) {}

    public function handle(object $event): void
    {
        if ($event instanceof OrderConfirmed === false) {
            return;
        }

        try {
            $this->orderNotificationService->notifyConfirmed($event->orderId, $event->projectId);
        } catch (\Throwable $e) {
            LogHelper::error('[Order] notifyConfirmed failed', [
                'order_id' => $event->orderId,
                'project_id' => $event->projectId,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $this->kitchenPushService->notifyNewOrder($event->orderId, $event->projectId);
        } catch (\Throwable $e) {
            LogHelper::error('[Kitchen] notifyNewOrder failed', [
                'order_id' => $event->orderId,
                'project_id' => $event->projectId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
