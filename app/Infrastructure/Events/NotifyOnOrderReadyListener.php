<?php

namespace App\Infrastructure\Events;

use App\Domain\Order\Events\OrderReady;
use App\Domain\Shared\DomainEventDispatcher\DomainEventListener;
use App\Services\OrderNotificationService;
use App\Util\LogHelper;

class NotifyOnOrderReadyListener implements DomainEventListener
{
    public function __construct(
        private OrderNotificationService $orderNotificationService,
    ) {}

    public function handle(object $event): void
    {
        if ($event instanceof OrderReady === false) {
            return;
        }

        try {
            $this->orderNotificationService->notifyReady($event->orderId, $event->projectId);
        } catch (\Throwable $e) {
            LogHelper::error('[Order] notifyReady failed', [
                'order_id' => $event->orderId,
                'project_id' => $event->projectId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
