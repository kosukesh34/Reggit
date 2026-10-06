<?php

namespace App\UseCases\Payment;

use Stripe\Checkout\Session as CheckoutSession;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentGateway implements PaymentGateway
{
    public function __construct(?string $secretKey)
    {
        // Keep the existing API contract when upgrading the Stripe SDK.
        Stripe::setApiVersion('2026-02-25.clover');

        if ($secretKey !== null && $secretKey !== '') {
            Stripe::setApiKey($secretKey);
        }
    }

    public function createPaymentIntent(int $amount, ?int $orderId): array
    {
        $intent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => 'jpy',
            'payment_method_types' => ['card'],
            'metadata' => [
                'order_id' => $orderId,
            ],
        ], ['idempotency_key' => 'reggit-order-'.$orderId.'-intent']);

        return [
            'client_secret' => $intent->client_secret,
            'id' => $intent->id,
        ];
    }

    public function createCheckoutSession(
        int $amount,
        int $orderId,
        string $successUrl,
        string $cancelUrl,
    ): array {
        $session = CheckoutSession::create([
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'jpy',
                    'unit_amount' => $amount,
                    'product_data' => ['name' => 'モバイルオーダー'],
                ],
            ]],
            'payment_intent_data' => [
                'metadata' => ['order_id' => $orderId],
            ],
            'metadata' => ['order_id' => $orderId],
        ], ['idempotency_key' => 'reggit-order-'.$orderId.'-checkout']);

        return [
            'url' => (string) $session->url,
            'id' => (string) $session->id,
        ];
    }

    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        $intent = PaymentIntent::retrieve($paymentIntentId);

        return [
            'status' => $intent->status,
            'amount' => (int) ($intent->amount ?? 0),
            'currency' => (string) ($intent->currency ?? ''),
            'metadata' => $intent->metadata ? $intent->metadata->toArray() : [],
        ];
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        $session = CheckoutSession::retrieve($sessionId);

        return [
            'payment_status' => (string) $session->payment_status,
            'payment_intent_id' => (string) $session->payment_intent,
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
            'amount' => isset($data->amount) ? (int) $data->amount : 0,
        ];
    }
}
