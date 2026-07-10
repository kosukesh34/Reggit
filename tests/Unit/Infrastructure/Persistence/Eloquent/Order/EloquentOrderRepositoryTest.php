<?php

namespace Tests\Unit\Infrastructure\Persistence\Eloquent\Order;

use App\Domain\Order\Aggregates\Order as OrderAggregate;
use App\Domain\Order\ValueObjects\OrderId;
use App\Domain\Order\ValueObjects\OrderLineItem;
use App\Domain\Order\ValueObjects\PendingOrder;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Infrastructure\Persistence\Eloquent\Order\EloquentOrderRepository;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EloquentOrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_by_id_and_user_id_注文をドメイン集約として返すこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 10,
            'points_earned' => 5,
            'order_type' => 'mobile',
        ]);

        $repository = new EloquentOrderRepository;
        $aggregate = $repository->findByIdAndUserId(
            (int) $order->id,
            (int) $user->id,
            (int) $project->id,
        );

        $this->assertNotNull($aggregate);
        $this->assertSame(10, $aggregate->pointsUsed());
        $this->assertSame(5, $aggregate->pointsEarned());
    }

    public function test_find_by_id_and_user_id_for_update_注文をドメイン集約として返すこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 10,
            'points_earned' => 5,
            'order_type' => 'mobile',
        ]);

        $repository = new EloquentOrderRepository;
        $aggregate = null;
        DB::transaction(function () use ($repository, $order, $user, $project, &$aggregate) {
            $aggregate = $repository->findByIdAndUserIdForUpdate(
                (int) $order->id,
                (int) $user->id,
                (int) $project->id,
            );
        });

        $this->assertNotNull($aggregate);
        $this->assertSame(10, $aggregate->pointsUsed());
        $this->assertSame(5, $aggregate->pointsEarned());
    }

    public function test_save_ステータスを永続化すること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 500,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $aggregate = new OrderAggregate(
            new OrderId((int) $order->id),
            (int) $project->id,
            (int) $user->id,
            'mobile',
            PaymentMethod::STRIPE,
            OrderStatus::CANCELLED,
            0,
            0,
        );

        $repository = new EloquentOrderRepository;
        $repository->save($aggregate);

        $this->assertSame(OrderStatus::CANCELLED, $order->fresh()->status);
    }

    public function test_persist_pending_注文と明細を永続化すること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $product = Product::factory()->create(['project_id' => $project->id, 'price' => 800]);

        $repository = new EloquentOrderRepository;
        $orderId = $repository->persistPending(
            new PendingOrder(
                (int) $project->id,
                (int) $user->id,
                'mobile',
                PaymentMethod::STRIPE,
                1600.0,
                0,
                'テスト注文',
            ),
            [
                new OrderLineItem((int) $product->id, 2, 800.0, null),
            ],
        );

        $order = Order::with('items')->findOrFail($orderId);
        $this->assertSame(1600.0, (float) $order->total_amount);
        $this->assertSame('テスト注文', $order->notes);
        $this->assertCount(1, $order->items);
        $this->assertSame(2, (int) $order->items->first()->quantity);
    }
}
