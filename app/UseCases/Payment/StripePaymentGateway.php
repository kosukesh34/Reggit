<?php

namespace App\UseCases\Payment;

use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentGateway implements PaymentGateway
{
    public function __construct(?string $secretKey)
    {
        if ($secretKey !== null && $secretKey !== '') {
            Stripe::setApiKey($secretKey);
        }
    }

    public function createPaymentIntent(int $amount, ?int $orderId): array
    {
        $intent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'jpy',
            'metadata' => [
                'order_id' => $orderId,
            ],
        ]);

        return [
            'client_secret' => $intent->client_secret,
            'id' => $intent->id,
        ];
    }

    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        $intent = PaymentIntent::retrieve($paymentIntentId);
        return [
            'status' => $intent->status,
            'metadata' => $intent->metadata ? $intent->metadata->toArray() : [],
        ];
    }

    public function constructWebhookEvent(string $payload, string $sigHeader, string $secret): array
    {
        $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        $data = $event->data?->object;
        $metadata = [];
        if ($data && isset($data->metadata)) {
            $metadata = $data->metadata->toArray();
        }

        return [
            'type' => $event->type,
            'metadata' => $metadata,
            'status' => $data->status ?? null,
        ];
    }
}
