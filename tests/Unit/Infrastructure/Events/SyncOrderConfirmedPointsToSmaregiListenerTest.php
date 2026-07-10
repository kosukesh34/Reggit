<?php

namespace Tests\Unit\Infrastructure\Events;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Infrastructure\Events\SyncOrderConfirmedPointsToSmaregiListener;
use App\Models\Member;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiPointsSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class SyncOrderConfirmedPointsToSmaregiListenerTest extends TestCase
{
    use RefreshDatabase;

    private function makeListener(SmaregiApiService $smaregiApi): SyncOrderConfirmedPointsToSmaregiListener
    {
        return new SyncOrderConfirmedPointsToSmaregiListener(
            app(OrderRepository::class),
            app(MemberRepository::class),
            new SmaregiPointsSyncService(app(MemberRepository::class), $smaregiApi),
        );
    }

    public function test_決済確定時に利用と獲得のネット差分をスマレジへ1回同期すること(): void
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
            'points_used' => 20,
            'points_earned' => 5,
            'order_type' => 'mobile',
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('syncCustomerPointsForProject')
            ->once()
            ->with((int) $project->id, 'cust-1', -15)
            ->andReturn(['customerId' => 'cust-1']);

        $this->makeListener($smaregiApi)->handle(new OrderConfirmed(
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

        $this->makeListener($smaregiApi)->handle(new OrderConfirmed(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));
    }

    public function test_同一注文の再配信ではスマレジへ二重同期しないこと(): void
    {
        Cache::flush();

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
            'points_used' => 10,
            'points_earned' => 3,
            'order_type' => 'mobile',
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('syncCustomerPointsForProject')
            ->once()
            ->with((int) $project->id, 'cust-1', -7)
            ->andReturn(['customerId' => 'cust-1']);

        $listener = $this->makeListener($smaregiApi);
        $event = new OrderConfirmed(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        );
        $listener->handle($event);
        $listener->handle($event);
    }

    public function test_同期失敗時は完了フラグを残さず再実行できること(): void
    {
        Cache::flush();

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
            'points_used' => 20,
            'points_earned' => 5,
            'order_type' => 'mobile',
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('syncCustomerPointsForProject')
            ->once()
            ->with((int) $project->id, 'cust-1', -15)
            ->andReturn(null);
        $smaregiApi->shouldReceive('syncCustomerPointsForProject')
            ->once()
            ->with((int) $project->id, 'cust-1', -15)
            ->andReturn(['customerId' => 'cust-1']);

        $listener = $this->makeListener($smaregiApi);
        $event = new OrderConfirmed(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        );
        $listener->handle($event);
        $listener->handle($event);
    }
}
