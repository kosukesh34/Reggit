<?php

namespace App\UseCases\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;

class PaymentWebhookUseCase
{
    private const EVENT_SUCCEEDED = 'payment_intent.succeeded';

    public function __construct(private PaymentGateway $gateway) {}

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

        $order = Order::find($orderId);
        if ($order === null) {
            return true;
        }

        $order->update([
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
        ]);

        return true;
    }
}
