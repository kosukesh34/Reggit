<?php

namespace Tests\Unit\Payment;

use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\UseCases\Payment\PaymentConfirmUseCase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

        $gateway = new FakePaymentGateway;
        $gateway->intent = ['status' => 'succeeded', 'metadata' => ['order_id' => $order->id]];

        $dispatcher = \Mockery::mock(DomainEventDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::type(OrderConfirmed::class));
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::on(function ($event) use ($order, $user): bool {
                return $event instanceof OrderPointsSyncRequested
                    && $event->orderId === (int) $order->id
                    && $event->projectId === 1
                    && $event->userId === (int) $user->id;
            }));

        $useCase = new PaymentConfirmUseCase(
            $gateway,
            app(OrderRepository::class),
            $dispatcher,
        );
        $result = $useCase->handle('pi_test_123', $order->id, 1);

        $this->assertTrue($result->isSuccess);
        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
        $this->assertSame(PaymentMethod::STRIPE, $order->fresh()->payment_method);
    }

    public function test_confirm_再実行時は注文を上書きせず成功すること(): void
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
        $gateway->intent = ['status' => 'succeeded', 'metadata' => ['order_id' => $order->id]];

        $dispatcher = \Mockery::mock(DomainEventDispatcher::class);
        $dispatcher->shouldReceive('dispatch')
            ->once()
            ->with(\Mockery::on(function ($event) use ($order, $user): bool {
                return $event instanceof OrderPointsSyncRequested
                    && $event->orderId === (int) $order->id
                    && $event->projectId === 1
                    && $event->userId === (int) $user->id;
            }));

        $useCase = new PaymentConfirmUseCase(
            $gateway,
            app(OrderRepository::class),
            $dispatcher,
        );
        $result = $useCase->handle('pi_test_123', $order->id, 1);

        $this->assertTrue($result->isSuccess);
        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
    }

    public function test_confirm_metadataのorder_id不一致は失敗すること(): void
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
        $gateway->intent = ['status' => 'succeeded', 'metadata' => ['order_id' => $order->id + 1]];

        $useCase = new PaymentConfirmUseCase(
            $gateway,
            app(OrderRepository::class),
            app(DomainEventDispatcher::class),
        );
        $result = $useCase->handle('pi_test_123', $order->id, 1);

        $this->assertFalse($result->isSuccess);
        $this->assertSame('決済情報と注文が一致しません', $result->message);
        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
    }

    public function test_confirm_他プロジェクトの注文は_model_not_found_exceptionになること(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $gateway = new FakePaymentGateway;
        $gateway->intent = ['status' => 'succeeded', 'metadata' => ['order_id' => $order->id]];

        $useCase = new PaymentConfirmUseCase(
            $gateway,
            app(OrderRepository::class),
            app(DomainEventDispatcher::class),
        );

        $this->expectException(ModelNotFoundException::class);
        $useCase->handle('pi_test_123', $order->id, (int) $other->id);
    }

    public function test_confirm_注文が無い場合は_model_not_found_exceptionになること(): void
    {
        $gateway = new FakePaymentGateway;
        $gateway->intent = ['status' => 'succeeded', 'metadata' => ['order_id' => 999999]];

        $useCase = new PaymentConfirmUseCase(
            $gateway,
            app(OrderRepository::class),
            app(DomainEventDispatcher::class),
        );

        $this->expectException(ModelNotFoundException::class);
        $useCase->handle('pi_test_123', 999999, 1);
    }
}
