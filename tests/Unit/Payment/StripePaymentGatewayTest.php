<?php

namespace Tests\Unit\Payment;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\UseCases\Payment\StripePaymentGateway;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Stripe\ApiRequestor;
use Stripe\Exception\InvalidRequestException;
use Stripe\HttpClient\ClientInterface;
use Stripe\Stripe;

class StripePaymentGatewayTest extends TestCase
{
    public static function cancellationCases(): array
    {
        return [
            'open checkout' => ['checkout_web', 'cs_fixture', [['status' => 'open', 'payment_status' => 'unpaid'], ['status' => 'expired', 'payment_status' => 'unpaid']], true],
            'expired checkout retry' => ['checkout', 'cs_fixture', [['status' => 'expired', 'payment_status' => 'unpaid']], true],
            'paid checkout' => ['checkout', 'cs_fixture', [['status' => 'complete', 'payment_status' => 'paid']], false],
            'processing checkout' => ['checkout', 'cs_fixture', [['status' => 'complete', 'payment_status' => 'unpaid']], false],
            'card intent' => ['card', 'pi_fixture', [['status' => 'requires_action'], ['status' => 'canceled']], true],
            'cancelled intent retry' => ['intent', 'pi_fixture', [['status' => 'canceled']], true],
            'paid intent' => ['card', 'pi_fixture', [['status' => 'succeeded']], false],
            'processing intent' => ['intent', 'pi_fixture', [['status' => 'processing']], false],
        ];
    }

    #[DataProvider('cancellationCases')]
    public function test_cancellation_stops_only_unpaid_sessions(string $channel, string $id, array $responses, bool $allowed): void
    {
        $originalClient = ApiRequestor::httpClient();
        $originalTelemetry = Stripe::getEnableTelemetry();
        $calls = 0;
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->exactly(count($responses)))->method('request')->willReturnCallback(
            function ($method, $url, $headers, $params) use ($channel, $id, $responses, &$calls): array {
                $this->assertSame($calls === 0 ? 'get' : 'post', $method);
                $checkout = str_starts_with($channel, 'checkout');
                $path = ($checkout ? '/v1/checkout/sessions/' : '/v1/payment_intents/').$id;
                if ($calls > 0) {
                    $path .= $checkout ? '/expire' : '/cancel';
                }
                $this->assertStringEndsWith($path, $url);
                $this->assertContains('Authorization: Bearer sk_test_original', $headers);
                $response = $responses[$calls++];

                return [json_encode(['id' => $id, 'object' => $checkout ? 'checkout.session' : 'payment_intent'] + $response), 200, []];
            }
        );
        try {
            Stripe::setEnableTelemetry(false);
            ApiRequestor::setHttpClient($client);
            if (! $allowed) {
                $this->expectException(OrderOperationException::class);
            }
            (new StripePaymentGateway('sk_test_original'))->cancelUnpaidSession($channel, $id);
        } finally {
            ApiRequestor::setHttpClient($originalClient);
            Stripe::setEnableTelemetry($originalTelemetry);
        }
    }

    public function test_payment_winning_expiration_race_is_not_cancelled(): void
    {
        $originalClient = ApiRequestor::httpClient();
        $client = $this->createMock(ClientInterface::class);
        $client->expects($this->exactly(2))->method('request')->willReturnOnConsecutiveCalls(
            ['{"id":"cs_fixture","object":"checkout.session","status":"open","payment_status":"unpaid"}', 200, []],
            ['{"error":{"type":"invalid_request_error","message":"Session is complete"}}', 400, []],
        );
        try {
            ApiRequestor::setHttpClient($client);
            $this->expectException(InvalidRequestException::class);
            (new StripePaymentGateway('sk_test_fixture'))->cancelUnpaidSession('checkout_web', 'cs_fixture');
        } finally {
            ApiRequestor::setHttpClient($originalClient);
        }
    }

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
