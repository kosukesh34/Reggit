<?php

namespace Tests\Unit\Payment;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\UseCases\Payment\PaymentConfirmUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class PaymentConfirmUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_成功時に注文が更新されること(): void
    {
        Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => 1]);
        $order = Order::create([
            'project_id' => 1,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $gateway = new FakePaymentGateway();
        $gateway->intent = ['status' => 'succeeded', 'metadata' => []];

        $useCase = new PaymentConfirmUseCase($gateway);
        $result = $useCase->handle('pi_test_123', $order->id);

        $this->assertTrue($result->isSuccess);
        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
        $this->assertSame(PaymentMethod::CARD, $order->fresh()->payment_method);
    }
}
