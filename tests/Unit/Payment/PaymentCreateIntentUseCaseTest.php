<?php

namespace Tests\Unit\Payment;

use App\UseCases\Payment\PaymentCreateIntentUseCase;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class PaymentCreateIntentUseCaseTest extends TestCase
{
    public function test_createIntent_決済IntentのDTOを返すこと(): void
    {
        $gateway = new FakePaymentGateway();
        $useCase = new PaymentCreateIntentUseCase($gateway);

        $dto = $useCase->handle(1200.0, 10);

        $this->assertSame('cs_test_123', $dto->clientSecret);
        $this->assertSame('pi_test_123', $dto->paymentIntentId);
        $this->assertSame(120000, $gateway->created['amount']);
        $this->assertSame(10, $gateway->created['order_id']);
    }
}
