<?php

namespace Tests\Feature;

use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Project;
use App\Models\Store;
use App\Models\User;
use App\Services\OrderNotificationOutbox;
use App\Services\OrderNumberAssigner;
use App\Services\OrderService;
use App\Services\PostCommitDomainEventDispatcher;
use App\Services\Smaregi\SmaregiPointsSyncService;
use App\UseCases\Payment\PaymentWebhookUseCase;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Mockery;
use Psr\Log\LoggerInterface;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class PostCommitEventTransactionTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_rollback_discards_delivery_but_records_inside_the_transaction(): void
    {
        $event = new \stdClass;
        $recordedLevel = null;
        $outbox = $this->mock(OrderNotificationOutbox::class);
        $outbox->shouldReceive('record')->once()->with($event)->andReturnUsing(function () use (&$recordedLevel): void {
            $recordedLevel = DB::transactionLevel();
        });
        $outbox->shouldNotReceive('deliverEvent');
        $dispatcher = $this->mock(DomainEventDispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');
        DB::beginTransaction();
        try {
            app(PostCommitDomainEventDispatcher::class)->dispatch($event);
            $this->assertSame(1, $recordedLevel);
        } finally {
            DB::rollBack();
        }
    }

    public function test_delivery_waits_for_the_outermost_commit(): void
    {
        $event = new \stdClass;
        $delivered = 0;
        $outbox = $this->mock(OrderNotificationOutbox::class);
        $outbox->shouldReceive('record')->once()->with($event);
        $outbox->shouldReceive('deliverEvent')->once()->with($event)->andReturnUsing(function () use (&$delivered): void {
            $this->assertSame(0, DB::transactionLevel());
            $delivered++;
        });
        $this->mock(DomainEventDispatcher::class)->shouldReceive('dispatch')->once()->with($event);
        DB::beginTransaction();
        try {
            DB::transaction(fn () => app(PostCommitDomainEventDispatcher::class)->dispatch($event));
            $this->assertSame(0, $delivered);
            DB::commit();
            $this->assertSame(1, $delivered);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
    }

    public function test_deferred_listener_uses_captured_headquarters_context_and_restores_caller_after_failure(): void
    {
        $original = Project::factory()->create();
        $later = Project::factory()->create();
        $context = app(StoreContext::class);
        AdminContext::setCurrentProject($original);
        $context->projectId = $original->id;
        $context->bound = true;
        $context->projectStoreCount = 3;
        $event = new \stdClass;
        $outbox = $this->mock(OrderNotificationOutbox::class);
        $outbox->shouldReceive('record')->once();
        $outbox->shouldReceive('deliverEvent')->once();
        $observed = null;
        $dispatcher = $this->mock(DomainEventDispatcher::class);
        $dispatcher->shouldReceive('dispatch')->once()->andReturnUsing(function () use (&$observed, $context): void {
            $observed = [AdminContext::getCurrentProjectId(), $context->projectId, $context->store,
                $context->visibleStoreIds, $context->admin, $context->bound, $context->projectStoreCount];
            throw new \RuntimeException('listener failed');
        });
        $this->mock(LoggerInterface::class)->shouldReceive('error')->once()->with('[DomainEvent] post-commit dispatch failed', Mockery::type('array'));
        DB::beginTransaction();
        try {
            app(PostCommitDomainEventDispatcher::class)->dispatch($event);
            AdminContext::setCurrentProject($later);
            $store = new Store(['project_id' => $later->id]);
            $context->projectId = $later->id;
            $context->store = $store;
            $context->visibleStoreIds = [123];
            $context->bound = false;
            $context->projectStoreCount = 1;
            DB::commit();
            $this->assertSame([$original->id, $original->id, null, null, null, true, 3], $observed);
            $this->assertSame($later->id, AdminContext::requireCurrentProjectId());
            $this->assertSame($later->id, $context->projectId);
            $this->assertSame($store, $context->store);
            $this->assertSame([123], $context->visibleStoreIds);
            $this->assertFalse($context->bound);
            $this->assertSame(1, $context->projectStoreCount);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
    }

    public function test_webhook_sync_job_uses_order_context_after_outer_commit_and_restores_later_selection(): void
    {
        config(['queue.default' => 'sync']);
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $store = Store::create(['project_id' => $project->id, 'store_key' => 'order', 'name' => 'Order store', 'is_active' => true]);
        $order = Order::create(['project_id' => $project->id, 'store_id' => $store->id, 'user_id' => $user->id,
            'total_amount' => 1000, 'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE, 'order_type' => OrderType::MOBILE]);
        $later = Project::factory()->create();
        $laterStore = Store::create(['project_id' => $later->id, 'store_key' => 'later', 'name' => 'Later store', 'is_active' => true]);
        $context = app(StoreContext::class);
        AdminContext::setCurrentProject($later);
        $context->projectId = $later->id;
        $context->store = $laterStore;
        $context->visibleStoreIds = [$laterStore->id];
        $context->bound = true;
        $outbox = $this->mock(OrderNotificationOutbox::class);
        $outbox->shouldReceive('record');
        $outbox->shouldReceive('deliverEvent');
        $this->mock(DomainEventDispatcher::class)->shouldReceive('dispatch');
        $observed = null;
        $this->mock(SmaregiPointsSyncService::class)->shouldReceive('syncOrderNetPoints')->once()
            ->with((int) $order->id, (int) $project->id, (int) $user->id)
            ->andReturnUsing(function () use (&$observed, $context): bool {
                $observed = [AdminContext::getCurrentProjectId(), $context->projectId, $context->store?->id,
                    $context->visibleStoreIds, DB::transactionLevel()];

                return true;
            });
        $gateway = new FakePaymentGateway;
        $gateway->event = ['type' => 'payment_intent.succeeded', 'currency' => 'jpy',
            'metadata' => ['order_id' => $order->id], 'amount' => 1000];
        $webhook = new PaymentWebhookUseCase($gateway, app(OrderRepository::class),
            app(PostCommitDomainEventDispatcher::class), app(OrderNumberAssigner::class), app(OrderService::class));
        DB::beginTransaction();
        try {
            $this->assertTrue($webhook->handle('{}', 'sig', 'secret'));
            $this->assertNull($observed);
            $this->assertSame($later->id, AdminContext::getCurrentProjectId());
            $this->assertSame($laterStore, $context->store);
            DB::commit();
            $this->assertSame([$project->id, $project->id, $store->id, [$store->id], 0], $observed);
            $this->assertSame($later->id, AdminContext::getCurrentProjectId());
            $this->assertSame($laterStore, $context->store);
            $this->assertSame([$laterStore->id], $context->visibleStoreIds);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }
    }
}
