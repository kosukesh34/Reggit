<?php

namespace App\UseCases\Payment;

use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Order\Events\OrderTransactionSyncRequested;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Enums\OrderStatus;
use App\Enums\PaymentIntentStatus;
use App\Models\Order;
use App\Services\OrderNumberAssigner;
use App\UseCases\Payment\Data\PaymentConfirmOutput;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class PaymentConfirmUseCase
{
    public function __construct(
        private PaymentGateway $gateway,
        private OrderRepository $orderRepository,
        private DomainEventDispatcher $domainEvents,
        private OrderNumberAssigner $orderNumberAssigner,
    ) {}

    public function handle(string $paymentIntentId, int $orderId, int $projectId): PaymentConfirmOutput
    {
        $intent = $this->gateway->retrievePaymentIntent($paymentIntentId);
        $status = $intent['status'] ?? null;
        if ($status !== PaymentIntentStatus::SUCCEEDED) {
            return new PaymentConfirmOutput(false, '決済が完了していません', (string) $status, null);
        }

        $metadataOrderId = isset($intent['metadata']['order_id'])
            ? (int) $intent['metadata']['order_id']
            : null;
        if ($metadataOrderId !== $orderId) {
            return new PaymentConfirmOutput(false, '決済情報と注文が一致しません', (string) $status, null);
        }

        $confirmedEvent = null;
        $pointsSyncEvent = null;
        $transactionSyncEvent = null;
        $output = DB::transaction(function () use ($orderId, $projectId, $status, &$confirmedEvent, &$pointsSyncEvent, &$transactionSyncEvent) {
            $aggregate = $this->orderRepository->findByIdForUpdate($orderId, $projectId);
            if ($aggregate === null) {
                throw (new ModelNotFoundException)->setModel(Order::class, [$orderId]);
            }

            try {
                $didConfirm = $aggregate->confirmStripePayment();
            } catch (\Exception $e) {
                return new PaymentConfirmOutput(false, $e->getMessage(), (string) $status, null);
            }

            $this->orderRepository->save($aggregate);

            if ($didConfirm) {
                $confirmedEvent = new OrderConfirmed(
                    $aggregate->id()->value(),
                    $aggregate->projectId(),
                    $aggregate->userId(),
                );
            }

            $this->orderNumberAssigner->assignIfMissing($orderId, $projectId);

            // 既確定の再送でも同期を要求する（失敗時リトライ。冪等は SyncService 側）。
            if ($this->shouldRequestPointsSync($aggregate->status(), $aggregate->isCancelled())) {
                $pointsSyncEvent = new OrderPointsSyncRequested(
                    $aggregate->id()->value(),
                    $aggregate->projectId(),
                    $aggregate->userId(),
                );
                $transactionSyncEvent = new OrderTransactionSyncRequested(
                    $aggregate->id()->value(),
                    $aggregate->projectId(),
                    $aggregate->userId(),
                );
            }

            return new PaymentConfirmOutput(
                true,
                '決済が完了しました',
                $status,
                Order::query()
                    ->whereKey($orderId)
                    ->where('project_id', $projectId)
                    ->firstOrFail(),
            );
        });

        if ($confirmedEvent !== null) {
            $this->domainEvents->dispatch($confirmedEvent);
        }
        if ($pointsSyncEvent !== null) {
            $this->domainEvents->dispatch($pointsSyncEvent);
        }
        if ($transactionSyncEvent !== null) {
            $this->domainEvents->dispatch($transactionSyncEvent);
        }

        return $output;
    }

    private function shouldRequestPointsSync(string $status, bool $isCancelled): bool
    {
        if ($isCancelled) {
            return false;
        }

        return $status !== OrderStatus::PENDING;
    }
}
