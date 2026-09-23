<?php

namespace App\UseCases\Payment;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class OrderPaymentSessionService
{
    private const LEASE_SECONDS = 120;

    public function __construct(private PaymentGateway $gateway) {}

    /** @return array<string, string> */
    public function create(int $orderId, int $projectId, int $userId, string $channel): array
    {
        $lease = bin2hex(random_bytes(16));
        $reservation = DB::transaction(function () use ($orderId, $projectId, $userId, $channel, $lease) {
            $order = Order::where('project_id', $projectId)->lockForUpdate()->findOrFail($orderId);
            $this->validateOrder($order, $userId);

            $saved = $order->payment_session;
            if (is_array($saved)) {
                if (($saved['channel'] ?? null) !== $channel) {
                    throw new InvalidArgumentException('作成済みの決済画面からお支払いください');
                }
                if (isset($saved['result'])) {
                    return ['result' => $saved['result']];
                }
                if (now()->lt($saved['expires_at'] ?? now())) {
                    throw new PaymentSessionInProgressException;
                }
            }

            $order->payment_session = [
                'channel' => $channel,
                'lease' => $lease,
                'expires_at' => now()->addSeconds(self::LEASE_SECONDS)->toIso8601String(),
            ];
            $order->save();

            return ['amount' => (int) $order->total_amount];
        });

        if (isset($reservation['result'])) {
            return $reservation['result'];
        }

        try {
            $result = $this->createAtGateway($channel, $reservation['amount'], $orderId);
        } catch (Throwable $e) {
            $this->clearReservation($orderId, $projectId, $lease);
            throw $e;
        }

        return DB::transaction(function () use ($orderId, $projectId, $lease, $channel, $result) {
            $order = Order::where('project_id', $projectId)->lockForUpdate()->findOrFail($orderId);
            $saved = $order->payment_session;
            if (($saved['lease'] ?? null) !== $lease || $order->status !== OrderStatus::PENDING) {
                throw new RuntimeException('決済の状態が変更されました');
            }
            $order->payment_session = ['channel' => $channel, 'result' => $result];
            $order->save();

            return $result;
        });
    }

    private function validateOrder(Order $order, int $userId): void
    {
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
    }

    /** @return array<string, string> */
    private function createAtGateway(string $channel, int $amount, int $orderId): array
    {
        if ($channel === 'intent') {
            return $this->gateway->createPaymentIntent($amount, $orderId);
        }

        $base = rtrim((string) config('app.url'), '/').'/mobile/payment/return';
        $result = $this->gateway->createCheckoutSession(
            $amount,
            $orderId,
            $base.'?status=success&order_id='.$orderId.'&session_id={CHECKOUT_SESSION_ID}',
            $base.'?status=cancel&order_id='.$orderId,
        );
        if (trim((string) ($result['url'] ?? '')) === '') {
            throw new RuntimeException('決済画面を作成できませんでした');
        }

        return $result;
    }

    private function clearReservation(int $orderId, int $projectId, string $lease): void
    {
        DB::transaction(function () use ($orderId, $projectId, $lease) {
            $order = Order::where('project_id', $projectId)->lockForUpdate()->findOrFail($orderId);
            if (($order->payment_session['lease'] ?? null) === $lease) {
                $order->payment_session = null;
                $order->save();
            }
        });
    }
}
