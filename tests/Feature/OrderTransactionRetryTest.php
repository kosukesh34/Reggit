<?php

namespace Tests\Feature;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Settings\Repositories\SmaregiSettings;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\OrderNumberAssigner;
use App\Services\OrderService;
use App\Services\PostCommitDomainEventDispatcher;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiOrderTransactionOutboundService;
use App\UseCases\Payment\PaymentConfirmUseCase;
use App\UseCases\Payment\PaymentWebhookUseCase;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class OrderTransactionRetryTest extends TestCase
{
    use RefreshDatabase;

    private function order(): Order
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);

        return Order::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'total_amount' => 1000, 'status' => OrderStatus::READY,
            'payment_method' => PaymentMethod::CASH, 'order_type' => OrderType::MOBILE,
        ]);
    }

    private function sync(?string $contract = 'contract-test'): mixed
    {
        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn($contract);
        $sync = Mockery::mock(SmaregiOrderTransactionOutboundService::class, [
            app(OrderRepository::class), app(MemberRepository::class), $api, app(SmaregiSettings::class),
        ])->makePartial();
        $this->app->instance(SmaregiOrderTransactionOutboundService::class, $sync);

        return $sync;
    }

    public function test_failed_creation_is_recovered_without_a_queue(): void
    {
        Queue::fake();
        $order = $this->order();
        $order->forceFill(['smaregi_create_pending' => true, 'smaregi_create_retry_at' => now()])->save();
        $previous = Project::factory()->create();
        AdminContext::setCurrentProject($previous);
        $sync = $this->sync();
        $sync->shouldReceive('syncOrderTransaction')->twice()->andReturn(false, true);
        try {
            $this->artisan('smaregi:retry-order-transactions')->assertSuccessful();
            $this->assertTrue($order->refresh()->smaregi_create_pending);
            $this->travel(1)->minutes();
            $this->artisan('smaregi:retry-order-transactions')->assertSuccessful();
            $this->assertFalse($order->refresh()->smaregi_create_pending);
            $this->assertNull($order->smaregi_create_leased_until);
            $this->assertSame($previous->id, AdminContext::getCurrentProject()?->id);
            $this->assertNull(app(StoreContext::class)->store);
            Queue::assertNothingPushed();
        } finally {
            AdminContext::setCurrentProject(null);
        }
    }

    public function test_false_result_and_expired_claim_remain_retryable(): void
    {
        $order = $this->order();
        $order->forceFill(['status' => OrderStatus::COMPLETED, 'smaregi_create_pending' => true,
            'smaregi_create_retry_at' => now(), 'smaregi_create_leased_until' => now()->addMinutes(5)])->save();
        $sync = $this->sync();
        $sync->shouldReceive('syncOrderTransaction')->once()->andReturn(false);
        $this->artisan('smaregi:retry-order-transactions')->assertSuccessful();
        $this->travel(5)->minutes();
        $this->artisan('smaregi:retry-order-transactions')->assertSuccessful();
        $this->assertTrue($order->refresh()->smaregi_create_pending);
        $this->assertTrue($order->smaregi_create_retry_at->isFuture());
    }

    public function test_old_worker_cannot_acknowledge_new_claim(): void
    {
        $order = $this->order();
        $order->forceFill(['status' => OrderStatus::COMPLETED, 'smaregi_create_pending' => true,
            'smaregi_create_retry_at' => now()])->save();
        $sync = $this->sync();
        $sync->shouldReceive('syncOrderTransaction')->once()->andReturnUsing(function () use ($order): bool {
            Order::withoutGlobalScopes()->whereKey($order->id)->update(['smaregi_create_leased_until' => now()->addMinutes(6)]);

            return true;
        });
        $this->artisan('smaregi:retry-order-transactions')->assertSuccessful();
        $this->assertTrue($order->refresh()->smaregi_create_pending);
        $this->assertSame(now()->addMinutes(6)->toDateTimeString(), $order->smaregi_create_leased_until->toDateTimeString());
    }

    public function test_unconfigured_optional_integration_waits_without_sending(): void
    {
        $order = $this->order();
        $order->forceFill(['status' => OrderStatus::COMPLETED, 'smaregi_create_pending' => true,
            'smaregi_create_retry_at' => now()])->save();
        $sync = $this->sync(null);
        $sync->shouldNotReceive('syncOrderTransaction');
        $this->artisan('smaregi:retry-order-transactions')->assertSuccessful();
        $this->assertTrue($order->refresh()->smaregi_create_pending);
        $this->assertSame(now()->addHour()->toDateTimeString(), $order->smaregi_create_retry_at->toDateTimeString());
    }

    public function test_completed_sync_is_acknowledged_even_after_configuration_is_removed(): void
    {
        $order = $this->order();
        $order->forceFill(['status' => OrderStatus::COMPLETED, 'smaregi_create_pending' => true,
            'smaregi_create_retry_at' => now(), 'smaregi_transaction_synced_at' => now()])->save();
        $sync = $this->sync(null);
        $sync->shouldNotReceive('syncOrderTransaction');
        $this->artisan('smaregi:retry-order-transactions')->assertSuccessful();
        $this->assertFalse($order->refresh()->smaregi_create_pending);
    }

    public function test_webhook_delay_is_preserved_on_replay_and_frontend_advances_only_unleased_work(): void
    {
        Queue::fake();
        $this->freezeTime();
        $order = $this->order();
        $order->forceFill(['payment_method' => PaymentMethod::STRIPE, 'status' => OrderStatus::PENDING])->save();
        $gateway = new FakePaymentGateway;
        $gateway->event = ['type' => 'payment_intent.succeeded',
            'metadata' => ['order_id' => $order->id], 'amount' => 1000];
        $gateway->intent = ['status' => 'succeeded', 'amount' => 1000, 'currency' => 'jpy',
            'metadata' => ['order_id' => $order->id]];
        $dispatcher = Mockery::mock(DomainEventDispatcher::class);
        $dispatcher->shouldReceive('dispatch');
        $events = app(PostCommitDomainEventDispatcher::class, ['dispatcher' => $dispatcher]);
        $webhook = new PaymentWebhookUseCase($gateway, app(OrderRepository::class),
            $events, app(OrderNumberAssigner::class), app(OrderService::class));
        $confirm = new PaymentConfirmUseCase($gateway, app(OrderRepository::class),
            $events, app(OrderNumberAssigner::class), app(OrderService::class));
        $webhook->handle('{}', 'sig', 'secret');
        $due = $order->refresh()->smaregi_create_retry_at;
        $this->assertSame(now()->addSeconds(20)->toDateTimeString(), $due->toDateTimeString());
        $this->travel(5)->seconds();
        $webhook->handle('{}', 'sig', 'secret');
        $this->assertTrue($order->refresh()->smaregi_create_retry_at->equalTo($due));
        $this->assertTrue($confirm->handle('pi_test', (int) $order->id, (int) $order->project_id, (int) $order->user_id)->isSuccess);
        $this->assertSame(now()->toDateTimeString(), $order->refresh()->smaregi_create_retry_at->toDateTimeString());
        $lease = now()->addMinutes(5);
        $order->forceFill(['smaregi_create_leased_until' => $lease])->save();
        $confirm->handle('pi_test', (int) $order->id, (int) $order->project_id, (int) $order->user_id);
        $this->assertSame($lease->toDateTimeString(), $order->refresh()->smaregi_create_leased_until->toDateTimeString());
    }

    public function test_frontend_request_is_rolled_back_with_payment_and_never_sent_before_commit(): void
    {
        $order = $this->order();
        $order->forceFill(['payment_method' => PaymentMethod::STRIPE, 'status' => OrderStatus::PENDING])->save();
        $gateway = new FakePaymentGateway;
        $gateway->intent = ['status' => 'succeeded', 'amount' => 1000, 'currency' => 'jpy',
            'metadata' => ['order_id' => $order->id]];
        $sync = $this->mock(SmaregiOrderTransactionOutboundService::class);
        $sync->shouldNotReceive('syncOrderTransactionWithRetry');
        $confirm = new PaymentConfirmUseCase($gateway, app(OrderRepository::class),
            app(PostCommitDomainEventDispatcher::class), app(OrderNumberAssigner::class),
            app(OrderService::class));
        DB::beginTransaction();
        try {
            $this->assertTrue($confirm->handle('pi_test', (int) $order->id, (int) $order->project_id, (int) $order->user_id)->isSuccess);
            $this->assertTrue($order->refresh()->smaregi_create_pending);
        } finally {
            DB::rollBack();
        }
        $this->assertSame(OrderStatus::PENDING, $order->refresh()->status);
        $this->assertFalse($order->smaregi_create_pending);
    }

    public function test_cancelled_order_is_acknowledged_without_creating_a_pos_transaction(): void
    {
        $order = $this->order();
        $order->forceFill(['status' => OrderStatus::CANCELLED, 'smaregi_create_pending' => true,
            'smaregi_create_retry_at' => now()])->save();
        $sync = $this->sync();
        $sync->shouldNotReceive('syncOrderTransaction');
        $this->artisan('smaregi:retry-order-transactions')->assertSuccessful();
        $this->assertFalse($order->refresh()->smaregi_create_pending);
    }
}
