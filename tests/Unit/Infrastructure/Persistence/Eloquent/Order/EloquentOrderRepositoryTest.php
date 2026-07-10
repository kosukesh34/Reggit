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

    public function test_try_claim_は同時に1件だけ取得できること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::CASH,
            'points_used' => 10,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $repository = new EloquentOrderRepository;
        $first = $repository->tryClaimSmaregiPointsSync((int) $order->id, (int) $project->id, 5);
        $second = $repository->tryClaimSmaregiPointsSync((int) $order->id, (int) $project->id, 5);

        $this->assertNotNull($first);
        $this->assertNull($second);
    }

    public function test_release_は自分の_claim_以外を消さないこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::CASH,
            'points_used' => 10,
            'points_earned' => 0,
            'order_type' => 'mobile',
            'smaregi_points_sync_started_at' => '2026-07-11 12:00:00',
        ]);

        $repository = new EloquentOrderRepository;
        $repository->releaseSmaregiPointsSyncClaim(
            (int) $order->id,
            (int) $project->id,
            '2026-07-11 11:00:00',
        );

        $order->refresh();
        $this->assertSame('2026-07-11 12:00:00', $order->smaregi_points_sync_started_at?->format('Y-m-d H:i:s'));

        $repository->releaseSmaregiPointsSyncClaim(
            (int) $order->id,
            (int) $project->id,
            '2026-07-11 12:00:00',
        );
        $order->refresh();
        $this->assertNull($order->smaregi_points_sync_started_at);
    }

    public function test_mark_後は_release_が_no_op_で_synced_at_を維持すること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::CASH,
            'points_used' => 10,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $repository = new EloquentOrderRepository;
        $claimedAt = $repository->tryClaimSmaregiPointsSync((int) $order->id, (int) $project->id, 5);
        $this->assertNotNull($claimedAt);

        $repository->markSmaregiPointsSynced((int) $order->id, (int) $project->id);
        $repository->releaseSmaregiPointsSyncClaim((int) $order->id, (int) $project->id, $claimedAt);

        $order->refresh();
        $this->assertNotNull($order->smaregi_points_synced_at);
        $this->assertNull($order->smaregi_points_sync_started_at);
        $this->assertTrue($repository->isSmaregiPointsSynced((int) $order->id, (int) $project->id));
    }

    public function test_find_unsynced_はポイント変動ありの未同期注文だけ返すこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $unsynced = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::CASH,
            'points_used' => 10,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);
        Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 500,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::CASH,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);
        Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 800,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::CASH,
            'points_used' => 5,
            'points_earned' => 0,
            'order_type' => 'mobile',
            'smaregi_points_synced_at' => now(),
        ]);

        $repository = new EloquentOrderRepository;
        $found = $repository->findUnsyncedSmaregiPointsOrdersForUser((int) $user->id, (int) $project->id);

        $this->assertCount(1, $found);
        $this->assertSame((int) $unsynced->id, $found[0]['orderId']);
    }
}
