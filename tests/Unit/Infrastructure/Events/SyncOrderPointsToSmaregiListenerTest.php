<?php

namespace Tests\Unit\Infrastructure\Events;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Infrastructure\Events\SyncOrderPointsToSmaregiListener;
use App\Jobs\SyncOrderPointsToSmaregiJob;
use App\Models\Member;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiPointsSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class SyncOrderPointsToSmaregiListenerTest extends TestCase
{
    use RefreshDatabase;

    private function makeListener(SmaregiApiService $smaregiApi): SyncOrderPointsToSmaregiListener
    {
        return new SyncOrderPointsToSmaregiListener(
            new SmaregiPointsSyncService(
                app(MemberRepository::class),
                app(OrderRepository::class),
                $smaregiApi,
            ),
        );
    }

    public function test_order_confirmedではスマレジ同期しないこと(): void
    {
        Queue::fake();

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
        $smaregiApi->shouldNotReceive('syncCustomerPointsForProject');

        $this->makeListener($smaregiApi)->handle(new OrderConfirmed(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));
    }

    public function test_order_points_sync_requestedで利用と獲得のネット差分をスマレジへ1回同期すること(): void
    {
        Queue::fake();

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
        $smaregiApi->shouldReceive('getContractIdForProject')
            ->andReturn('contract-1');
        $smaregiApi->shouldReceive('syncCustomerPointsForProject')
            ->once()
            ->with((int) $project->id, 'cust-1', -15)
            ->andReturn(['customerId' => 'cust-1']);

        $this->makeListener($smaregiApi)->handle(new OrderPointsSyncRequested(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));

        $order->refresh();
        $this->assertNotNull($order->smaregi_points_synced_at);
    }

    public function test_ポイント変動がなければ同期しないこと(): void
    {
        Queue::fake();

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

        $this->makeListener($smaregiApi)->handle(new OrderPointsSyncRequested(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));

        $order->refresh();
        $this->assertNotNull($order->smaregi_points_synced_at);
    }

    public function test_同一注文の再配信ではスマレジへ二重同期しないこと(): void
    {
        Queue::fake();

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
        $smaregiApi->shouldReceive('getContractIdForProject')
            ->andReturn('contract-1');
        $smaregiApi->shouldReceive('syncCustomerPointsForProject')
            ->once()
            ->with((int) $project->id, 'cust-1', -7)
            ->andReturn(['customerId' => 'cust-1']);

        $listener = $this->makeListener($smaregiApi);
        $event = new OrderPointsSyncRequested(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        );
        $listener->handle($event);
        $listener->handle($event);

        $order->refresh();
        $this->assertNotNull($order->smaregi_points_synced_at);
    }

    public function test_同期失敗時は完了フラグを残さず再実行できること(): void
    {
        Queue::fake();

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
        $smaregiApi->shouldReceive('getContractIdForProject')
            ->andReturn('contract-1');
        $smaregiApi->shouldReceive('syncCustomerPointsForProject')
            ->once()
            ->with((int) $project->id, 'cust-1', -15)
            ->andReturn(null);
        $smaregiApi->shouldReceive('syncCustomerPointsForProject')
            ->once()
            ->with((int) $project->id, 'cust-1', -15)
            ->andReturn(['customerId' => 'cust-1']);

        $listener = $this->makeListener($smaregiApi);
        $pointsSync = new SmaregiPointsSyncService(
            app(MemberRepository::class),
            app(OrderRepository::class),
            $smaregiApi,
        );
        $event = new OrderPointsSyncRequested(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        );
        $listener->handle($event);
        $this->assertTrue($pointsSync->hasOutboundPending((int) $member->id));
        $order->refresh();
        $this->assertNull($order->smaregi_points_synced_at);
        Queue::assertPushed(SyncOrderPointsToSmaregiJob::class);

        $listener->handle($event);
        $this->assertFalse($pointsSync->hasOutboundPending((int) $member->id));
        $order->refresh();
        $this->assertNotNull($order->smaregi_points_synced_at);
    }

    public function test_契約未設定なら同期をスキップし完了にしないこと(): void
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
            'points_used' => 10,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')
            ->andReturn(null);
        $smaregiApi->shouldNotReceive('syncCustomerPointsForProject');

        $pointsSync = new SmaregiPointsSyncService(
            app(MemberRepository::class),
            app(OrderRepository::class),
            $smaregiApi,
        );

        $this->assertFalse($pointsSync->syncOrderNetPoints(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));

        $order->refresh();
        $this->assertNull($order->smaregi_points_synced_at);
    }

    public function test_顧客未連携なら完了にせず後から連携したときに同期すること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '',
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 10,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')
            ->andReturn('contract-1');
        $smaregiApi->shouldReceive('syncCustomerPointsForProject')
            ->once()
            ->with((int) $project->id, 'cust-later', -10)
            ->andReturn(['customerId' => 'cust-later']);

        $pointsSync = new SmaregiPointsSyncService(
            app(MemberRepository::class),
            app(OrderRepository::class),
            $smaregiApi,
        );

        $this->assertFalse($pointsSync->syncOrderNetPoints(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));
        $order->refresh();
        $this->assertNull($order->smaregi_points_synced_at);

        $member->smaregi_customer_id = 'cust-later';
        $member->save();

        $this->assertTrue($pointsSync->syncOrderNetPoints(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));
        $order->refresh();
        $this->assertNotNull($order->smaregi_points_synced_at);
    }

    public function test_claim競合中に完了したら成功扱いになること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 10,
            'points_earned' => 0,
            'order_type' => 'mobile',
            'smaregi_points_sync_started_at' => now(),
            'smaregi_points_synced_at' => now(),
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldNotReceive('syncCustomerPointsForProject');

        $pointsSync = new SmaregiPointsSyncService(
            app(MemberRepository::class),
            app(OrderRepository::class),
            $smaregiApi,
        );

        $this->assertTrue($pointsSync->syncOrderNetPoints(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));
    }

    public function test_outbound_pendingは成功するまで残ること(): void
    {
        Cache::flush();

        $pointsSync = new SmaregiPointsSyncService(
            app(MemberRepository::class),
            app(OrderRepository::class),
            Mockery::mock(SmaregiApiService::class),
        );

        $pointsSync->markOutboundPending(99);
        $this->assertTrue($pointsSync->hasOutboundPending(99));

        $this->travel(2)->days();
        $this->assertTrue($pointsSync->hasOutboundPending(99));

        $pointsSync->clearOutboundPending(99);
        $this->assertFalse($pointsSync->hasOutboundPending(99));
    }
}
