<?php

namespace Tests\Unit\Payment;

use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\UseCases\Payment\PaymentCreateCheckoutUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class PaymentCreateCheckoutUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_注文金額と復帰先を渡すこと(): void
    {
        config(['app.url' => 'https://reggit.example']);
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1480,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => OrderType::MOBILE,
        ]);
        $gateway = new FakePaymentGateway;
        $useCase = new PaymentCreateCheckoutUseCase($gateway, app(OrderRepository::class));

        $output = $useCase->handle((int) $order->id, (int) $project->id, (int) $user->id);

        $this->assertSame('https://checkout.stripe.test/session', $output->checkoutUrl);
        $this->assertSame(1480, $gateway->checkoutCreated['amount']);
        $this->assertStringContainsString('status=success', $gateway->checkoutCreated['successUrl']);
        $this->assertStringContainsString('order_id='.$order->id, $gateway->checkoutCreated['successUrl']);
        $this->assertStringContainsString('{CHECKOUT_SESSION_ID}', $gateway->checkoutCreated['successUrl']);
        $this->assertStringContainsString('status=cancel', $gateway->checkoutCreated['cancelUrl']);
    }
}
