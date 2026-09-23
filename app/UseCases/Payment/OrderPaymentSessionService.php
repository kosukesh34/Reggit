<?php

namespace App\UseCases\Payment;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderPaymentSessionService
{
    public function __construct(private PaymentGateway $gateway) {}

    /** @return array<string, string> */
    public function create(int $orderId, int $projectId, int $userId, string $channel): array
    {
        return DB::transaction(function () use ($orderId, $projectId, $userId, $channel) {
            $order = Order::where('project_id', $projectId)->lockForUpdate()->findOrFail($orderId);
            if ((int) $order->user_id !== $userId) {
                throw new InvalidArgumentException('注文の所有者と一致しません');
            }
            if ($order->order_type !== OrderType::MOBILE || $order->payment_method !== PaymentMethod::STRIPE) {
                throw new InvalidArgumentException('カード払いのモバイル注文のみ決済できます');
            }
            if ($order->status !== OrderStatus::PENDING) {
                throw new InvalidArgumentException('未決済の注文のみ決済できます');
            }
            if ((int) $order->total_amount < 1) {
                throw new InvalidArgumentException('決済金額が不正です');
            }
            $saved = $order->payment_session;
            if (is_array($saved)) {
                if (($saved['channel'] ?? null) !== $channel) {
                    throw new InvalidArgumentException('作成済みの決済画面からお支払いください');
                }

                return $saved['result'];
            }
            if ($channel === 'intent') {
                $result = $this->gateway->createPaymentIntent((int) $order->total_amount, $orderId);
            } else {
                $base = rtrim((string) config('app.url'), '/').'/mobile/payment/return';
                $result = $this->gateway->createCheckoutSession(
                    (int) $order->total_amount,
                    $orderId,
                    $base.'?status=success&order_id='.$orderId.'&session_id={CHECKOUT_SESSION_ID}',
                    $base.'?status=cancel&order_id='.$orderId,
                );
                if (trim((string) ($result['url'] ?? '')) === '') {
                    throw new \RuntimeException('決済画面を作成できませんでした');
                }
            }
            $order->payment_session = ['channel' => $channel, 'result' => $result];
            $order->save();

            return $result;
        });
    }
}
