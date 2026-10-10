<?php

namespace Tests\Feature;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Settings\Repositories\SmaregiSettings;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\OrderStatusService;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiOrderTransactionOutboundService;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class CashPickupRetryTest extends TestCase
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

    public function test_failed_pickup_keeps_completed_order_and_recovers_with_sync_queue(): void
    {
        Queue::fake();
        config(['queue.default' => 'sync']);
        $order = $this->order();
        $previous = Project::factory()->create();
        AdminContext::setCurrentProject($previous);
        $sync = $this->sync();
        $sync->shouldReceive('finalizeCashLayaway')->twice()->andReturnUsing(function () use ($order): bool {
            static $attempt = 0;
            if (++$attempt === 1) {
                throw new \RuntimeException('POS unavailable');
            }
            $this->assertSame((int) $order->project_id, (int) AdminContext::getCurrentProject()?->id);
            $this->assertSame((int) $order->store_id, app(StoreContext::class)->store?->id);

            return true;
        });
        try {
            // Explicit tenant binding for the state-changing request.
            AdminContext::setCurrentProject(Project::findOrFail($order->project_id));
            app(OrderStatusService::class)->update((int) $order->id, OrderStatus::COMPLETED);
            $this->assertSame(OrderStatus::COMPLETED, $order->refresh()->status);
            $this->assertTrue($order->smaregi_pickup_pending);
            app(OrderStatusService::class)->update((int) $order->id, OrderStatus::COMPLETED);
            AdminContext::setCurrentProject($previous);
            $this->artisan('smaregi:retry-cash-pickups')->assertSuccessful();
            $this->assertTrue($order->refresh()->smaregi_pickup_pending);
            $this->travel(1)->minutes();
            $this->artisan('smaregi:retry-cash-pickups')->assertSuccessful();
            $this->assertFalse($order->refresh()->smaregi_pickup_pending);
            $this->assertNull($order->smaregi_pickup_retry_at);
            $this->assertSame($previous->id, AdminContext::getCurrentProject()?->id);
            $this->assertNull(app(StoreContext::class)->store);
        } finally {
            AdminContext::setCurrentProject(null);
        }
    }

    public function test_false_result_and_expired_claim_remain_retryable(): void
    {
        $order = $this->order();
        $order->forceFill(['status' => OrderStatus::COMPLETED, 'smaregi_pickup_pending' => true,
            'smaregi_pickup_retry_at' => now()->addMinutes(5)])->save();
        $sync = $this->sync();
        $sync->shouldReceive('finalizeCashLayaway')->once()->andReturn(false);
        $this->artisan('smaregi:retry-cash-pickups')->assertSuccessful();
        $this->travel(5)->minutes();
        $this->artisan('smaregi:retry-cash-pickups')->assertSuccessful();
        $this->assertTrue($order->refresh()->smaregi_pickup_pending);
        $this->assertTrue($order->smaregi_pickup_retry_at->isFuture());
    }

    public function test_old_worker_cannot_acknowledge_new_claim(): void
    {
        $order = $this->order();
        $order->forceFill(['status' => OrderStatus::COMPLETED, 'smaregi_pickup_pending' => true,
            'smaregi_pickup_retry_at' => now()])->save();
        $sync = $this->sync();
        $sync->shouldReceive('finalizeCashLayaway')->once()->andReturnUsing(function () use ($order): bool {
            Order::withoutGlobalScopes()->whereKey($order->id)->update(['smaregi_pickup_retry_at' => now()->addMinutes(6)]);

            return true;
        });
        $this->artisan('smaregi:retry-cash-pickups')->assertSuccessful();
        $this->assertTrue($order->refresh()->smaregi_pickup_pending);
        $this->assertSame(now()->addMinutes(6)->toDateTimeString(), $order->smaregi_pickup_retry_at->toDateTimeString());
    }

    public function test_unconfigured_optional_integration_waits_without_sending(): void
    {
        $order = $this->order();
        $order->forceFill(['status' => OrderStatus::COMPLETED, 'smaregi_pickup_pending' => true,
            'smaregi_pickup_retry_at' => now()])->save();
        $sync = $this->sync(null);
        $sync->shouldNotReceive('finalizeCashLayaway');
        $this->artisan('smaregi:retry-cash-pickups')->assertSuccessful();
        $this->assertTrue($order->refresh()->smaregi_pickup_pending);
        $this->assertSame(now()->addHour()->toDateTimeString(), $order->smaregi_pickup_retry_at->toDateTimeString());
    }

    public function test_completed_sync_is_acknowledged_even_after_configuration_is_removed(): void
    {
        $order = $this->order();
        $order->forceFill(['status' => OrderStatus::COMPLETED, 'smaregi_pickup_pending' => true,
            'smaregi_pickup_retry_at' => now(), 'smaregi_transaction_synced_at' => now()])->save();
        $sync = $this->sync(null);
        $sync->shouldNotReceive('finalizeCashLayaway');
        $this->artisan('smaregi:retry-cash-pickups')->assertSuccessful();
        $this->assertFalse($order->refresh()->smaregi_pickup_pending);
    }

    public function test_pickup_request_rolls_back_with_order_transition(): void
    {
        $order = $this->order();
        $sync = $this->mock(SmaregiOrderTransactionOutboundService::class);
        $sync->shouldNotReceive('finalizeCashLayawayWithRetry');
        DB::beginTransaction();
        try {
            app(OrderStatusService::class)->update((int) $order->id, OrderStatus::COMPLETED);
            $this->assertTrue($order->refresh()->smaregi_pickup_pending);
        } finally {
            DB::rollBack();
        }
        $this->assertSame(OrderStatus::READY, $order->refresh()->status);
        $this->assertFalse($order->smaregi_pickup_pending);
    }
}
