<?php

namespace App\UseCases\Payment;

use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Enums\OrderStatus;
use App\Jobs\SyncOrderPointsToSmaregiJob;
use App\Jobs\SyncOrderTransactionToSmaregiJob;
use App\Models\Order;
use App\Services\OrderNumberAssigner;
use App\Services\OrderService;
use App\Util\LogHelper;
use Illuminate\Support\Facades\DB;

class PaymentWebhookUseCase
{
    private const EVENT_SUCCEEDED = 'payment_intent.succeeded';

    /** フロントの決済完了より先にスマレジ Webhook が来ないよう遅延する秒数 */
    private const SMAREGI_SYNC_DELAY_SECONDS = 20;

    public function __construct(
        private PaymentGateway $gateway,
        private OrderRepository $orderRepository,
        private DomainEventDispatcher $domainEvents,
        private OrderNumberAssigner $orderNumberAssigner,
        private OrderService $orderService,
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
        $shouldDelaySmaregiSync = false;
        $syncOrderId = 0;
        $syncProjectId = 0;
        $syncUserId = 0;
        DB::transaction(function () use (
            $orderId,
            &$confirmedEvent,
            &$shouldDelaySmaregiSync,
            &$syncOrderId,
            &$syncProjectId,
            &$syncUserId,
        ) {
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

                $order = Order::query()->whereKey((int) $orderId)->first();
                if ($order !== null) {
                    $this->orderService->applyStripePaymentRewardsIfNeeded($order);
                }

                // Stripe Webhook 経路は即時同期しない（決済完了 UI より先にスマレジ Webhook が来るのを防ぐ）
                // フロント confirm が来ない場合のフォールバックとして遅延ジョブを積む
                if ($this->shouldRequestPointsSync($aggregate->status(), $aggregate->isCancelled())) {
                    $shouldDelaySmaregiSync = true;
                    $syncOrderId = $aggregate->id()->value();
                    $syncProjectId = $aggregate->projectId();
                    $syncUserId = $aggregate->userId();
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
        if ($shouldDelaySmaregiSync) {
            SyncOrderPointsToSmaregiJob::dispatch($syncOrderId, $syncProjectId, $syncUserId)
                ->delay(now()->addSeconds(self::SMAREGI_SYNC_DELAY_SECONDS));
            SyncOrderTransactionToSmaregiJob::dispatch($syncOrderId, $syncProjectId, $syncUserId)
                ->delay(now()->addSeconds(self::SMAREGI_SYNC_DELAY_SECONDS));
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
