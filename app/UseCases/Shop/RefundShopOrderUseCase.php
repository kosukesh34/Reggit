<?php

namespace App\UseCases\Shop;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Services\OrderCancellationReverser;
use App\Services\OrderNotificationService;
use App\UseCases\Payment\PaymentGateway;
use App\UseCases\Payment\ProjectPaymentGatewayResolver;
use App\Util\LogHelper;
use Illuminate\Support\Facades\DB;

/** 配送注文の全額返金。Stripe で返金し、注文をキャンセルして在庫・ポイントを戻す。 */
class RefundShopOrderUseCase
{
    public function __construct(
        private PaymentGateway $gateway,
        private ProjectPaymentGatewayResolver $resolver,
        private OrderCancellationReverser $cancellations,
        private OrderNotificationService $notifications,
    ) {}

    public function handle(Order $target): void
    {
        $order = DB::transaction(function () use ($target): Order {
            $order = Order::withoutGlobalScopes()->where('project_id', $target->project_id)->lockForUpdate()->findOrFail($target->id);
            if ($order->order_type !== OrderType::SHIPPING || $order->payment_method !== PaymentMethod::STRIPE
                || in_array($order->status, [OrderStatus::PENDING, OrderStatus::CANCELLED], true) || $order->refunded_at !== null) {
                throw new OrderOperationException('この注文は返金できません');
            }
            $amount = (int) $order->total_amount;
            // 同じ注文の再実行は Stripe 側で同じ返金として扱われる
            $refundId = $this->gateway($order)->refundPayment($this->paymentIntentId($order), $amount, 'reggit-order-'.$order->id.'-refund');
            $order->forceFill([
                'status' => OrderStatus::CANCELLED,
                'refunded_at' => now(),
                'refund_amount' => $amount,
                'stripe_refund_id' => $refundId,
            ])->save();

            return $order;
        });

        $this->cancellations->handleOrderCancellation($order);
        try {
            $this->notifications->emailShopOrder((int) $order->id, (int) $order->project_id, 'refunded');
        } catch (\Throwable $e) {
            // 返金自体は完了しているため、メールの失敗で操作を失敗にしない
            LogHelper::error('[Shop] refund mail failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    private function gateway(Order $order): PaymentGateway
    {
        try {
            return $this->resolver->forOrder($order, $this->gateway);
        } catch (\InvalidArgumentException $e) {
            throw new OrderOperationException($e->getMessage());
        }
    }

    private function paymentIntentId(Order $order): string
    {
        $session = $order->payment_session ?? [];
        $id = $session['result']['id'] ?? null;
        if (! is_string($id) || $id === '') {
            throw new OrderOperationException('決済情報が見つからないため返金できません。Stripe の管理画面から返金してください');
        }
        if (in_array($session['channel'] ?? null, ['checkout', 'checkout_web'], true)) {
            $id = (string) ($this->gateway($order)->retrieveCheckoutSession($id)['payment_intent_id'] ?? '');
            if ($id === '') {
                throw new OrderOperationException('決済情報を確認できないため返金できません');
            }
        }

        return $id;
    }
}
