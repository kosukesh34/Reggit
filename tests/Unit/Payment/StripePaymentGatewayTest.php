<?php

namespace Tests\Unit\Payment;

use App\UseCases\Payment\StripePaymentGateway;
use PHPUnit\Framework\TestCase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\Stripe;

class StripePaymentGatewayTest extends TestCase
{
    public function test_sdk_update_preserves_payment_api_contract(): void
    {
        $originalClient = ApiRequestor::httpClient();
        $originalKey = Stripe::getApiKey();
        $originalVersion = Stripe::getApiVersion();
        $originalTelemetry = Stripe::getEnableTelemetry();
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->once())->method('request')->willReturnCallback(
            function ($method, $url, $headers, $params): array {
                $this->assertSame('post', $method);
                $this->assertStringEndsWith('/v1/payment_intents', $url);
                $this->assertContains('Stripe-Version: 2026-02-25.clover', $headers);
                $this->assertContains('Idempotency-Key: reggit-order-42-intent', $headers);
                $this->assertSame(1480, $params['amount']);
                $this->assertSame('jpy', $params['currency']);
                $this->assertSame(['card'], $params['payment_method_types']);
                $this->assertArrayNotHasKey('receipt_email', $params);

                return ['{"id":"pi_fixture","object":"payment_intent","client_secret":"fixture_secret"}', 200, []];
            }
        );

        try {
            Stripe::setEnableTelemetry(false);
            ApiRequestor::setHttpClient($client);
            $result = (new StripePaymentGateway('sk_test_fixture'))->createPaymentIntent(1480, 42);
            $this->assertSame(['client_secret' => 'fixture_secret', 'id' => 'pi_fixture'], $result);
        } finally {
            ApiRequestor::setHttpClient($originalClient);
            Stripe::setApiKey($originalKey);
            Stripe::setApiVersion($originalVersion);
            Stripe::setEnableTelemetry($originalTelemetry);
        }
    }
}
