<?php

namespace App\UseCases\Payment;

use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Enums\OrderStatus;
use App\Services\OrderNumberAssigner;
use App\Util\LogHelper;
use Illuminate\Support\Facades\DB;

class PaymentWebhookUseCase
{
    private const EVENT_SUCCEEDED = 'payment_intent.succeeded';

    public function __construct(
        private PaymentGateway $gateway,
        private OrderRepository $orderRepository,
        private DomainEventDispatcher $domainEvents,
        private OrderNumberAssigner $orderNumberAssigner,
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

        $confirmedEvent = null;
        $pointsSyncEvent = null;
        DB::transaction(function () use ($orderId, &$confirmedEvent, &$pointsSyncEvent) {
            $aggregate = $this->orderRepository->findByPrimaryKeyForUpdate((int) $orderId);
            if ($aggregate === null) {
                return;
            }

            try {
                $didConfirm = $aggregate->confirmStripePayment();
                $this->orderRepository->save($aggregate);
                if ($didConfirm) {
                    $confirmedEvent = new OrderConfirmed(
                        $aggregate->id()->value(),
                        $aggregate->projectId(),
                        $aggregate->userId(),
                    );
                }
                $this->orderNumberAssigner->assignIfMissing(
                    $aggregate->id()->value(),
                    $aggregate->projectId(),
                );

                if ($this->shouldRequestPointsSync($aggregate->status(), $aggregate->isCancelled())) {
                    $pointsSyncEvent = new OrderPointsSyncRequested(
                        $aggregate->id()->value(),
                        $aggregate->projectId(),
                        $aggregate->userId(),
                    );
                }
            } catch (\Exception $e) {
                LogHelper::warning('[Payment] webhook confirm skipped', [
                    'order_id' => (int) $orderId,
                    'project_id' => $aggregate->projectId(),
                    'error' => $e->getMessage(),
                ]);
            }
        });

        if ($confirmedEvent !== null) {
            $this->domainEvents->dispatch($confirmedEvent);
        }
        if ($pointsSyncEvent !== null) {
            $this->domainEvents->dispatch($pointsSyncEvent);
        }

        return true;
    }

    private function shouldRequestPointsSync(string $status, bool $isCancelled): bool
    {
        if ($isCancelled) {
            return false;
        }

        return $status !== OrderStatus::PENDING;
    }
}
