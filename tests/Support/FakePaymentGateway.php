<?php

namespace Tests\Support;

use App\UseCases\Payment\PaymentGateway;

class FakePaymentGateway implements PaymentGateway
{
    public array $created = [];

    public array $checkoutCreated = [];

    public array $intent = [];

    public array $event = [];

    public function createPaymentIntent(int $amount, ?int $orderId): array
    {
        $this->created = ['amount' => $amount, 'order_id' => $orderId];

        return [
            'client_secret' => 'cs_test_123',
            'id' => 'pi_test_123',
        ];
    }

    public function createCheckoutSession(
        int $amount,
        int $orderId,
        string $successUrl,
        string $cancelUrl,
    ): array {
        $this->checkoutCreated = compact('amount', 'orderId', 'successUrl', 'cancelUrl');

        return [
            'url' => 'https://checkout.stripe.test/session',
            'id' => 'cs_test_123',
        ];
    }

    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        return $this->intent ?: [
            'status' => 'succeeded',
            'amount' => 0,
            'currency' => 'jpy',
            'metadata' => [],
        ];
    }

    public function constructWebhookEvent(string $payload, string $sigHeader, string $secret): array
    {
        return $this->event ?: [
            'type' => 'payment_intent.succeeded',
            'metadata' => [],
            'status' => 'succeeded',
            'amount' => 0,
        ];
    }
}
