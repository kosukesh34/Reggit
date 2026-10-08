<?php

namespace Tests\Unit\Payment;

use App\UseCases\Payment\StripePaymentGateway;
use PHPUnit\Framework\TestCase;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Stripe\Stripe;

class StripePaymentGatewayTest extends TestCase
{
    public function test_checkout_isolates_account_keys_and_paypay_method(): void
    {
        $originalClient = ApiRequestor::httpClient();
        $originalKey = Stripe::getApiKey();
        $originalTelemetry = Stripe::getEnableTelemetry();
        $requests = [];
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->exactly(4))->method('request')->willReturnCallback(
            function ($method, $url, $headers, $params) use (&$requests): array {
                $this->assertSame('post', $method);
                $this->assertStringEndsWith('/v1/checkout/sessions', $url);
                $requests[] = compact('headers', 'params');

                return ['{"id":"cs_fixture","object":"checkout.session","url":"https://checkout.stripe.com/fixture"}', 200, []];
            }
        );
        try {
            Stripe::setEnableTelemetry(false);
            ApiRequestor::setHttpClient($client);
            $one = new StripePaymentGateway('sk_test_one', 'paypay', ['project_id' => '11', 'payment_setting_id' => '21']);
            $two = new StripePaymentGateway('sk_test_two', 'apple_pay', ['project_id' => '12', 'payment_setting_id' => '22']);
            $one->createCheckoutSession(980, 41, 'https://example.com/success', 'https://example.com/cancel');
            $two->createCheckoutSession(980, 42, 'https://example.com/success', 'https://example.com/cancel');
            $one->createCheckoutSession(980, 41, 'https://example.com/success', 'https://example.com/cancel');
            (new StripePaymentGateway('sk_test_google', 'google_pay'))->createCheckoutSession(980, 43, 'https://example.com/success', 'https://example.com/cancel');
            $this->assertSame(['card'], $requests[3]['params']['payment_method_types']);
            $this->assertContains('Authorization: Bearer sk_test_google', $requests[3]['headers']);
            $this->assertContains('Authorization: Bearer sk_test_one', $requests[0]['headers']);
            $this->assertContains('Authorization: Bearer sk_test_two', $requests[1]['headers']);
            $this->assertContains('Authorization: Bearer sk_test_one', $requests[2]['headers']);
            $this->assertSame(['paypay'], $requests[0]['params']['payment_method_types']);
            $this->assertSame(['card'], $requests[1]['params']['payment_method_types']);
            $this->assertSame('11', $requests[0]['params']['payment_intent_data']['metadata']['project_id']);
            $this->assertSame('22', $requests[1]['params']['metadata']['payment_setting_id']);
            $this->assertSame($originalKey, Stripe::getApiKey());
        } finally {
            ApiRequestor::setHttpClient($originalClient);
            Stripe::setEnableTelemetry($originalTelemetry);
        }
    }

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
