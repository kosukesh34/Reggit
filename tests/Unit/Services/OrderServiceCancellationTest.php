<?php

namespace Tests\Unit\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PointTransactionType;
use App\Infrastructure\Persistence\Eloquent\Member\EloquentMemberRepository;
use App\Models\Member;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_handleOrderCancellation_利用ポイント返却と獲得ポイント取り消しを行うこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'points' => 0,
        ]);
        PointTransaction::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'type' => PointTransactionType::EARNED,
            'points' => 50,
            'description' => '初期',
        ]);
        $member->recalcPointsFromTransactions();

        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CANCELLED,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 20,
            'points_earned' => 5,
            'order_type' => 'mobile',
        ]);

        $service = app(OrderService::class);
        $service->handleOrderCancellation($order);

        $member->refresh();
        $this->assertSame(65, $member->getDisplayPoints());
        $this->assertDatabaseHas('point_transactions', [
            'order_id' => $order->id,
            'type' => PointTransactionType::REFUNDED,
            'points' => 20,
        ]);
        $this->assertDatabaseHas('point_transactions', [
            'order_id' => $order->id,
            'type' => PointTransactionType::REVERSED,
            'points' => -5,
        ]);
    }

    public function test_handleOrderCancellation_二重実行しても台帳が重複しないこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'points' => 0,
        ]);
        PointTransaction::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'type' => PointTransactionType::EARNED,
            'points' => 50,
            'description' => '初期',
        ]);
        $member->recalcPointsFromTransactions();

        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 500,
            'status' => OrderStatus::CANCELLED,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 10,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $service = app(OrderService::class);
        $service->handleOrderCancellation($order);
        $service->handleOrderCancellation($order);

        $member->refresh();
        $this->assertSame(60, $member->getDisplayPoints());
        $this->assertSame(1, PointTransaction::query()
            ->where('order_id', $order->id)
            ->where('type', PointTransactionType::REFUNDED)
            ->count());
    }

    public function test_hasPointTransactionForOrder_説明付きで判定できること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 100,
            'status' => OrderStatus::CANCELLED,
            'payment_method' => PaymentMethod::CASH,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'in_store',
        ]);
        PointTransaction::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'order_id' => $order->id,
            'type' => PointTransactionType::REFUNDED,
            'points' => 10,
            'description' => '注文キャンセルによる返却',
        ]);

        $repository = app(EloquentMemberRepository::class);

        $this->assertTrue($repository->hasPointTransactionForOrder(
            (int) $order->id,
            PointTransactionType::REFUNDED,
            '注文キャンセルによる返却',
        ));
        $this->assertFalse($repository->hasPointTransactionForOrder(
            (int) $order->id,
            PointTransactionType::REFUNDED,
            '別の説明',
        ));
    }
}
