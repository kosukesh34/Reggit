<?php

namespace App\UseCases\Payment;

interface PaymentGateway
{
    public function createPaymentIntent(int $amount, ?int $orderId): array;

    public function retrievePaymentIntent(string $paymentIntentId): array;

    public function constructWebhookEvent(string $payload, string $sigHeader, string $secret): array;
}
