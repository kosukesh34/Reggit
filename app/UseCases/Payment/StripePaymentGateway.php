<?php

namespace App\UseCases\Payment;

use App\Domain\Order\Exceptions\OrderOperationException;
use Stripe\StripeClient;
use Stripe\Webhook;

class StripePaymentGateway implements PaymentGateway, ProjectCredentialedGateway
{
    private StripeClient $client;

    public function __construct(?string $secretKey, private string $brand = 'card', private array $metadata = [])
    {
        $this->client = new StripeClient([
            'api_key' => $secretKey ?: null,
            'stripe_version' => '2026-02-25.clover',
        ]);
    }

    public function withProjectCredentials(string $secretKey, string $brand, array $metadata): PaymentGateway
    {
        return new self($secretKey, $brand, $metadata);
    }

    public function createPaymentIntent(int $amount, ?int $orderId): array
    {
        $intent = $this->client->paymentIntents->create([
            'amount' => $amount,
            'currency' => 'jpy',
            'payment_method_types' => ['card'],
            'metadata' => ['order_id' => $orderId] + $this->metadata,
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
        $session = $this->client->checkout->sessions->create([
            'mode' => 'payment',
            'payment_method_types' => [$this->brand === 'paypay' ? 'paypay' : 'card'],
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
                'metadata' => ['order_id' => $orderId] + $this->metadata,
            ],
            'metadata' => ['order_id' => $orderId] + $this->metadata,
        ], ['idempotency_key' => 'reggit-order-'.$orderId.'-checkout']);

        return [
            'url' => (string) $session->url,
            'id' => (string) $session->id,
        ];
    }

    public function retrievePaymentIntent(string $paymentIntentId): array
    {
        $intent = $this->client->paymentIntents->retrieve($paymentIntentId, ['expand' => ['payment_method']]);

        return [
            'status' => $intent->status,
            'paid_payment_brand' => $intent->payment_method?->card?->wallet?->type ?? $intent->payment_method?->type ?? null,
            'amount' => (int) ($intent->amount ?? 0),
            'currency' => (string) ($intent->currency ?? ''),
            'metadata' => $intent->metadata ? $intent->metadata->toArray() : [],
        ];
    }

    public function retrieveCheckoutSession(string $sessionId): array
    {
        $session = $this->client->checkout->sessions->retrieve($sessionId);

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
            'currency' => $data->currency ?? null,
            'metadata' => $metadata,
            'status' => $data->status ?? null,
            'amount' => isset($data->amount) ? (int) $data->amount : 0,
        ];
    }

    public function refundPayment(string $paymentIntentId, int $amount, string $idempotencyKey): string
    {
        $refund = $this->client->refunds->create([
            'payment_intent' => $paymentIntentId,
            'amount' => $amount,
        ], ['idempotency_key' => $idempotencyKey]);

        return (string) $refund->id;
    }

    public function cancelUnpaidSession(string $channel, string $sessionId): void
    {
        if (in_array($channel, ['checkout', 'checkout_web'], true)) {
            $session = $this->client->checkout->sessions->retrieve($sessionId);
            if ($session->payment_status !== 'unpaid' || ! in_array($session->status, ['open', 'expired'], true)) {
                throw new OrderOperationException('決済済みまたは処理中の注文はキャンセルできません');
            }
            if ($session->status === 'open') {
                // Expiration fails if payment wins the race after the retrieval.
                $session = $this->client->checkout->sessions->expire($sessionId);
            }
            if ($session->status !== 'expired' || $session->payment_status !== 'unpaid') {
                throw new OrderOperationException('決済を停止できませんでした');
            }

            return;
        }

        if (! in_array($channel, ['intent', 'card'], true)) {
            throw new OrderOperationException('決済情報を確認できません');
        }
        $intent = $this->client->paymentIntents->retrieve($sessionId);
        if ($intent->status === 'canceled') {
            return;
        }
        if (! in_array($intent->status, ['requires_payment_method', 'requires_confirmation', 'requires_action'], true)) {
            throw new OrderOperationException('決済済みまたは処理中の注文はキャンセルできません');
        }
        $intent = $this->client->paymentIntents->cancel($sessionId, ['cancellation_reason' => 'requested_by_customer']);
        if ($intent->status !== 'canceled') {
            throw new OrderOperationException('決済を停止できませんでした');
        }
    }
}
