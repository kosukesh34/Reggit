<?php

namespace Tests\Unit\UseCases\Order;

use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\OrderService;
use App\UseCases\Order\OrderCancelUnpaidUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class OrderCancelUnpaidUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_未決済注文をキャンセルすること(): void
    {
        $project = Project::factory()->create();
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

        $orderService = Mockery::mock(OrderService::class);
        $orderService->shouldReceive('handleOrderCancellation')->once();

        $useCase = new OrderCancelUnpaidUseCase(
            app(OrderRepository::class),
            $orderService,
        );

        $result = $useCase->handle($user, (int) $order->id);

        $this->assertSame(OrderStatus::CANCELLED, $result->status);
    }

    public function test_handle_決済済み注文は拒否されること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $useCase = new OrderCancelUnpaidUseCase(
            app(OrderRepository::class),
            Mockery::mock(OrderService::class),
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('決済済みまたは進行中の注文はキャンセルできません');

        $useCase->handle($user, (int) $order->id);
    }

    public function test_handle_キャンセル済みは副作用なしで返すこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CANCELLED,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $orderService = Mockery::mock(OrderService::class);
        $orderService->shouldNotReceive('handleOrderCancellation');

        $useCase = new OrderCancelUnpaidUseCase(
            app(OrderRepository::class),
            $orderService,
        );

        $result = $useCase->handle($user, (int) $order->id);

        $this->assertSame(OrderStatus::CANCELLED, $result->status);
    }
}
