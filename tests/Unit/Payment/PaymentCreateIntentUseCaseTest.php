<?php

namespace Tests\Unit\Payment;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\Member\CurrentMemberPrincipal;
use App\UseCases\Payment\OrderPaymentSessionService;
use App\UseCases\Payment\PaymentCreateIntentUseCase;
use App\UseCases\Payment\ProjectPaymentGatewayResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class PaymentCreateIntentUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_intent_注文金額を円単位で渡すこと(): void
    {
        Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => 1]);
        $order = Order::create([
            'project_id' => 1,
            'user_id' => $user->id,
            'total_amount' => 1200,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => OrderType::MOBILE,
        ]);

        $gateway = new FakePaymentGateway;
        $useCase = new PaymentCreateIntentUseCase(new OrderPaymentSessionService($gateway, app(CurrentMemberPrincipal::class), app(ProjectPaymentGatewayResolver::class)));

        $dto = $useCase->handle((int) $order->id, (int) $order->project_id, $user);

        $this->assertSame('cs_test_123', $dto->clientSecret);
        $this->assertSame('pi_test_123', $dto->paymentIntentId);
        $this->assertSame(1200, $gateway->created['amount']);
        $this->assertSame((int) $order->id, $gateway->created['order_id']);
    }

    public function test_create_intent_他ユーザーの注文は拒否すること(): void
    {
        Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $owner = User::factory()->create(['project_id' => 1]);
        $other = User::factory()->create(['project_id' => 1]);
        $order = Order::create([
            'project_id' => 1,
            'user_id' => $owner->id,
            'total_amount' => 1200,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => OrderType::MOBILE,
        ]);

        $useCase = new PaymentCreateIntentUseCase(new OrderPaymentSessionService(new FakePaymentGateway, app(CurrentMemberPrincipal::class), app(ProjectPaymentGatewayResolver::class)));

        $this->expectException(InvalidArgumentException::class);
        $useCase->handle((int) $order->id, (int) $order->project_id, $other);
    }
}
