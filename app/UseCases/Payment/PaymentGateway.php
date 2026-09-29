<?php

namespace App\UseCases\Payment;

interface PaymentGateway
{
    public function createPaymentIntent(int $amount, ?int $orderId): array;

    /** @return array{url: string, id: string} */
    public function createCheckoutSession(
        int $amount,
        int $orderId,
        string $successUrl,
        string $cancelUrl,
    ): array;

    public function retrievePaymentIntent(string $paymentIntentId): array;

    public function retrieveCheckoutSession(string $sessionId): array;

    public function constructWebhookEvent(string $payload, string $sigHeader, string $secret): array;
}
