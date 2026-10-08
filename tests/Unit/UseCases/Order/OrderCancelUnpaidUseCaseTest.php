<?php

namespace Tests\Unit\UseCases\Order;

use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointTransaction;
use App\Models\Product;
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

    public function test_cancellation_side_effect_failure_rolls_back_and_can_be_retried(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $product = Product::factory()->create(['project_id' => $project->id, 'stock' => 3]);
        $order = Order::create([
            'project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 1000,
            'status' => OrderStatus::PENDING, 'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 20, 'points_earned' => 0, 'order_type' => 'mobile',
        ]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        PointTransaction::create(['project_id' => $project->id, 'member_id' => $member->id, 'type' => 'earned', 'points' => 100]);
        PointTransaction::create(['project_id' => $project->id, 'member_id' => $member->id, 'order_id' => $order->id, 'type' => 'used', 'points' => -20]);
        $member->recalcPointsFromTransactions();
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2, 'price' => 500]);
        $service = app(OrderService::class);
        $failingService = Mockery::mock(OrderService::class);
        $failingService->shouldReceive('handleOrderCancellation')->once()->andReturnUsing(function (Order $cancelled) use ($service): void {
            $service->handleOrderCancellation($cancelled);
            throw new \RuntimeException('fixture failure after stock restoration');
        });
        try {
            (new OrderCancelUnpaidUseCase(app(OrderRepository::class), $failingService))->handle($user, $order->id);
            $this->fail('Expected cancellation failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('fixture failure after stock restoration', $e->getMessage());
        }
        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame(80, $member->fresh()->getDisplayPoints());
        $this->assertDatabaseMissing('point_transactions', ['order_id' => $order->id, 'type' => 'refunded']);
        $useCase = new OrderCancelUnpaidUseCase(app(OrderRepository::class), $service);
        $useCase->handle($user, $order->id);
        $this->assertSame(OrderStatus::CANCELLED, $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(100, $member->fresh()->getDisplayPoints());
        $useCase->handle($user, $order->id);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(100, $member->fresh()->getDisplayPoints());
    }
}
