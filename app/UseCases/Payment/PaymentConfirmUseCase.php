<?php

namespace App\UseCases\Payment;

use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Order\Events\OrderTransactionSyncRequested;
use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\PaymentIntentStatus;
use App\Models\Order;
use App\Services\OrderNumberAssigner;
use App\Services\OrderService;
use App\Services\PostCommitDomainEventDispatcher;
use App\UseCases\Payment\Data\PaymentConfirmOutput;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class PaymentConfirmUseCase
{
    public function __construct(
        private PaymentGateway $gateway,
        private OrderRepository $orderRepository,
        private PostCommitDomainEventDispatcher $postCommitEvents,
        private OrderNumberAssigner $orderNumberAssigner,
        private OrderService $orderService,
    ) {}

    public function handle(string $paymentIntentId, int $orderId, int $projectId, int $userId): PaymentConfirmOutput
    {
        $paymentOrder = Order::where('project_id', $projectId)->where('user_id', $userId)->findOrFail($orderId);
        $gateway = app(ProjectPaymentGatewayResolver::class)->forOrder($paymentOrder, $this->gateway);
        $intent = $gateway->retrievePaymentIntent($paymentIntentId);
        if ($paymentOrder->payment_setting_id !== null
            && ((int) ($intent['metadata']['project_id'] ?? 0) !== $projectId
                || (int) ($intent['metadata']['payment_setting_id'] ?? 0) !== (int) $paymentOrder->payment_setting_id
                || ($intent['currency'] ?? '') !== 'jpy')) {
            return new PaymentConfirmOutput(false, '決済情報とプロジェクトが一致しません', (string) ($intent['status'] ?? ''), null);
        }
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
        $output = DB::transaction(function () use ($orderId, $projectId, $userId, $status, $intent, &$confirmedEvent, &$pointsSyncEvent, &$transactionSyncEvent) {
            $aggregate = $this->orderRepository->findByIdForUpdate($orderId, $projectId);
            if ($aggregate === null) {
                throw (new ModelNotFoundException)->setModel(Order::class, [$orderId]);
            }

            if ($aggregate->userId() !== $userId) {
                return new PaymentConfirmOutput(false, '注文の所有者と一致しません', (string) $status, null);
            }

            $paidAmount = (int) ($intent['amount'] ?? 0);
            if ($paidAmount !== $aggregate->totalAmount()) {
                return new PaymentConfirmOutput(false, '決済金額と注文金額が一致しません', (string) $status, null);
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

            $order = Order::query()
                ->whereKey($orderId)
                ->where('project_id', $projectId)
                ->firstOrFail();
            if (isset($intent['paid_payment_brand'])) {
                $order->paid_payment_brand = $intent['paid_payment_brand'];
                $order->save();
            }
            $this->orderService->applyStripePaymentRewardsIfNeeded($order);

            if ($confirmedEvent !== null) {
                $this->postCommitEvents->record($confirmedEvent);
            }

            // フロント決済完了経路では即時スマレジ同期（Webhook 遅延より先に送る）
            if ($aggregate->shouldSyncToSmaregi()) {
                if ($order->smaregi_transaction_synced_at === null) {
                    $order->smaregi_create_pending = true;
                    // Frontend completion may advance the webhook's delayed request.
                    if ($order->smaregi_create_leased_until === null
                        && ($order->smaregi_create_retry_at === null || $order->smaregi_create_retry_at->isFuture())) {
                        $order->smaregi_create_retry_at = now();
                    }
                    $order->save();
                }
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
                $order->fresh(),
            );
        });

        if ($confirmedEvent !== null) {
            $this->postCommitEvents->dispatch($confirmedEvent);
        }
        if ($pointsSyncEvent !== null) {
            $this->postCommitEvents->dispatch($pointsSyncEvent);
        }
        if ($transactionSyncEvent !== null) {
            $this->postCommitEvents->dispatch($transactionSyncEvent);
        }

        return $output;
    }
}
