<?php

namespace Tests\Unit\Infrastructure\Events;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Infrastructure\Events\SyncOrderConfirmedPointsToSmaregiListener;
use App\Infrastructure\Events\SyncPointsEarnedToSmaregiListener;
use App\Models\Member;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\Smaregi\SmaregiApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SyncOrderConfirmedPointsToSmaregiListenerTest extends TestCase
{
    use RefreshDatabase;

    public function test_決済確定時に利用と獲得の差分をスマレジへ同期すること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => 'cust-1',
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 20,
            'points_earned' => 5,
            'order_type' => 'mobile',
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('syncCustomerPointsForProject')
            ->once()
            ->with((int) $project->id, 'cust-1', -20)
            ->andReturn(['customerId' => 'cust-1']);
        $smaregiApi->shouldReceive('syncCustomerPointsForProject')
            ->once()
            ->with((int) $project->id, 'cust-1', 5)
            ->andReturn(['customerId' => 'cust-1']);

        $pointsSync = new SyncPointsEarnedToSmaregiListener(
            app(MemberRepository::class),
            $smaregiApi,
        );
        $listener = new SyncOrderConfirmedPointsToSmaregiListener(
            app(OrderRepository::class),
            app(MemberRepository::class),
            $pointsSync,
        );

        $listener->handle(new OrderConfirmed(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));
    }

    public function test_ポイント変動がなければ同期しないこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => 'cust-1',
        ]);
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

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldNotReceive('syncCustomerPointsForProject');

        $pointsSync = new SyncPointsEarnedToSmaregiListener(
            app(MemberRepository::class),
            $smaregiApi,
        );
        $listener = new SyncOrderConfirmedPointsToSmaregiListener(
            app(OrderRepository::class),
            app(MemberRepository::class),
            $pointsSync,
        );

        $listener->handle(new OrderConfirmed(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));
    }
}
