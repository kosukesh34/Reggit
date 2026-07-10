<?php

namespace App\UseCases\Payment;

use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Util\LogHelper;
use Illuminate\Support\Facades\DB;

class PaymentWebhookUseCase
{
    private const EVENT_SUCCEEDED = 'payment_intent.succeeded';

    public function __construct(
        private PaymentGateway $gateway,
        private OrderRepository $orderRepository,
        private DomainEventDispatcher $domainEvents,
    ) {}

    public function handle(string $payload, string $sigHeader, string $secret): bool
    {
        $event = $this->gateway->constructWebhookEvent($payload, $sigHeader, $secret);
        if (($event['type'] ?? null) !== self::EVENT_SUCCEEDED) {
            return true;
        }

        $orderId = $event['metadata']['order_id'] ?? null;
        if ($orderId === null) {
            return true;
        }

        return DB::transaction(function () use ($orderId) {
            $aggregate = $this->orderRepository->findByPrimaryKeyForUpdate((int) $orderId);
            if ($aggregate === null) {
                return true;
            }

            try {
                $didConfirm = $aggregate->confirmStripePayment();
                $this->orderRepository->save($aggregate);
                if ($didConfirm) {
                    $this->domainEvents->dispatch(new OrderConfirmed(
                        $aggregate->id()->value(),
                        $aggregate->projectId(),
                        $aggregate->userId(),
                    ));
                }
            } catch (\Exception $e) {
                LogHelper::warning('[Payment] webhook confirm skipped', [
                    'order_id' => (int) $orderId,
                    'project_id' => $aggregate->projectId(),
                    'error' => $e->getMessage(),
                ]);
            }

            return true;
        });
    }
}
