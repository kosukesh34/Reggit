<?php

namespace Tests\Unit\Payment;

use App\Domain\Order\Repositories\OrderRepository;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Jobs\SyncOrderPointsToSmaregiJob;
use App\Jobs\SyncOrderTransactionToSmaregiJob;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\OrderNumberAssigner;
use App\Services\OrderService;
use App\Services\PostCommitDomainEventDispatcher;
use App\Support\DailySequenceNumber;
use App\UseCases\Payment\PaymentWebhookUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class PaymentWebhookUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_決済成功で注文を確定すること(): void
    {
        Queue::fake();

        Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => 1]);
        $order = Order::create([
            'project_id' => 1,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $gateway = new FakePaymentGateway;
        $gateway->event = [
            'type' => 'payment_intent.succeeded', 'currency' => 'jpy',
            'metadata' => ['order_id' => $order->id],
            'amount' => 1000,
        ];

        $useCase = new PaymentWebhookUseCase(
            $gateway,
            app(OrderRepository::class),
            app(PostCommitDomainEventDispatcher::class),
            app(OrderNumberAssigner::class),
            app(OrderService::class),
        );
        $this->freezeTime();
        $result = $useCase->handle('{}', 'sig', 'secret');

        $this->assertTrue($result);
        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::CONFIRMED, $fresh->status);
        $this->assertSame(
            DailySequenceNumber::build(1),
            $fresh->order_number
        );
        Queue::assertNotPushed(SyncOrderTransactionToSmaregiJob::class);
        $this->assertTrue($order->refresh()->smaregi_create_pending);
        $this->assertSame(now()->addSeconds(20)->toDateTimeString(), $order->smaregi_create_retry_at->toDateTimeString());
        Queue::assertPushed(SyncOrderPointsToSmaregiJob::class);
    }

    public function test_handle_対象外注文は成功扱いのままスキップすること(): void
    {
        Queue::fake();

        Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => 1]);
        $order = Order::create([
            'project_id' => 1,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::CASH,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'in_store',
        ]);

        $gateway = new FakePaymentGateway;
        $gateway->event = [
            'type' => 'payment_intent.succeeded', 'currency' => 'jpy',
            'metadata' => ['order_id' => $order->id],
            'amount' => 1000,
        ];

        $useCase = new PaymentWebhookUseCase(
            $gateway,
            app(OrderRepository::class),
            app(PostCommitDomainEventDispatcher::class),
            app(OrderNumberAssigner::class),
            app(OrderService::class),
        );
        $result = $useCase->handle('{}', 'sig', 'secret');

        $this->assertTrue($result);
        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
        Queue::assertNothingPushed();
    }

    public function test_handle_既に確定済みなら遅延同期ジョブを積むこと(): void
    {
        $this->freezeTime();
        Queue::fake();

        Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => 1]);
        $order = Order::create([
            'project_id' => 1,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'mobile',
            'order_number' => DailySequenceNumber::build(1),
        ]);

        $gateway = new FakePaymentGateway;
        $gateway->event = [
            'type' => 'payment_intent.succeeded', 'currency' => 'jpy',
            'metadata' => ['order_id' => $order->id],
            'amount' => 1000,
        ];

        $dispatcher = \Mockery::mock(DomainEventDispatcher::class);
        $dispatcher->shouldNotReceive('dispatch');

        $useCase = new PaymentWebhookUseCase(
            $gateway,
            app(OrderRepository::class),
            app(PostCommitDomainEventDispatcher::class, ['dispatcher' => $dispatcher]),
            app(OrderNumberAssigner::class),
            app(OrderService::class),
        );
        $result = $useCase->handle('{}', 'sig', 'secret');

        $this->assertTrue($result);
        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
        $this->assertSame(DailySequenceNumber::build(1), $order->fresh()->order_number);
        Queue::assertNotPushed(SyncOrderTransactionToSmaregiJob::class);
        $this->assertTrue($order->refresh()->smaregi_create_pending);
        $this->assertSame(now()->addSeconds(20)->toDateTimeString(), $order->smaregi_create_retry_at->toDateTimeString());
        Queue::assertPushed(SyncOrderPointsToSmaregiJob::class);
    }

    public function test_handle_進行中注文はステータスを上書きしないこと(): void
    {
        Queue::fake();

        Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => 1]);
        $order = Order::create([
            'project_id' => 1,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::PREPARING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'mobile',
            'order_number' => DailySequenceNumber::build(5),
        ]);

        $gateway = new FakePaymentGateway;
        $gateway->event = [
            'type' => 'payment_intent.succeeded', 'currency' => 'jpy',
            'metadata' => ['order_id' => $order->id],
            'amount' => 1000,
        ];

        $useCase = new PaymentWebhookUseCase(
            $gateway,
            app(OrderRepository::class),
            app(PostCommitDomainEventDispatcher::class),
            app(OrderNumberAssigner::class),
            app(OrderService::class),
        );
        $result = $useCase->handle('{}', 'sig', 'secret');

        $this->assertTrue($result);
        $this->assertSame(OrderStatus::PREPARING, $order->fresh()->status);
        $this->assertSame(DailySequenceNumber::build(5), $order->fresh()->order_number);
    }

    public function test_handle_特典付与に失敗した場合は決済確定もロールバックすること(): void
    {
        Queue::fake();

        Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => 1]);
        $order = Order::create([
            'project_id' => 1,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $gateway = new FakePaymentGateway;
        $gateway->event = [
            'type' => 'payment_intent.succeeded', 'currency' => 'jpy',
            'metadata' => ['order_id' => $order->id],
            'amount' => 1000,
        ];

        $orderService = \Mockery::mock(OrderService::class);
        $orderService->shouldReceive('applyStripePaymentRewardsIfNeeded')
            ->once()
            ->andThrow(new \RuntimeException('reward persistence failed'));

        $useCase = new PaymentWebhookUseCase(
            $gateway,
            app(OrderRepository::class),
            app(PostCommitDomainEventDispatcher::class),
            app(OrderNumberAssigner::class),
            $orderService,
        );

        try {
            $useCase->handle('{}', 'sig', 'secret');
            $this->fail('Expected the reward failure to escape the transaction.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('reward persistence failed', $exception->getMessage());
        }

        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::PENDING, $fresh->status);
        $this->assertNull($fresh->order_number);
        Queue::assertNothingPushed();
    }
}
