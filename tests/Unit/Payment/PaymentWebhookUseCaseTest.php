<?php

namespace Tests\Unit\Payment;

use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\UseCases\Payment\PaymentWebhookUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class PaymentWebhookUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_決済成功で注文を確定すること(): void
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

        $gateway = new FakePaymentGateway;
        $gateway->event = [
            'type' => 'payment_intent.succeeded',
            'metadata' => ['order_id' => $order->id],
        ];

        $useCase = new PaymentWebhookUseCase(
            $gateway,
            app(OrderRepository::class),
            app(DomainEventDispatcher::class),
        );
        $result = $useCase->handle('{}', 'sig', 'secret');

        $this->assertTrue($result);
        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
    }

    public function test_handle_対象外注文は成功扱いのままスキップすること(): void
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
            'payment_method' => PaymentMethod::CASH,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'in_store',
        ]);

        $gateway = new FakePaymentGateway;
        $gateway->event = [
            'type' => 'payment_intent.succeeded',
            'metadata' => ['order_id' => $order->id],
        ];

        $useCase = new PaymentWebhookUseCase(
            $gateway,
            app(OrderRepository::class),
            app(DomainEventDispatcher::class),
        );
        $result = $useCase->handle('{}', 'sig', 'secret');

        $this->assertTrue($result);
        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
    }

    public function test_handle_既に確定済みなら再確定しても成功すること(): void
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
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $gateway = new FakePaymentGateway;
        $gateway->event = [
            'type' => 'payment_intent.succeeded',
            'metadata' => ['order_id' => $order->id],
        ];

        $dispatcher = \Mockery::mock(DomainEventDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::type(OrderPointsSyncRequested::class));

        $useCase = new PaymentWebhookUseCase(
            $gateway,
            app(OrderRepository::class),
            $dispatcher,
        );
        $result = $useCase->handle('{}', 'sig', 'secret');

        $this->assertTrue($result);
        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
    }

    public function test_handle_進行中注文はステータスを上書きしないこと(): void
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
            'status' => OrderStatus::PREPARING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $gateway = new FakePaymentGateway;
        $gateway->event = [
            'type' => 'payment_intent.succeeded',
            'metadata' => ['order_id' => $order->id],
        ];

        $useCase = new PaymentWebhookUseCase(
            $gateway,
            app(OrderRepository::class),
            app(DomainEventDispatcher::class),
        );
        $result = $useCase->handle('{}', 'sig', 'secret');

        $this->assertTrue($result);
        $this->assertSame(OrderStatus::PREPARING, $order->fresh()->status);
    }
}
