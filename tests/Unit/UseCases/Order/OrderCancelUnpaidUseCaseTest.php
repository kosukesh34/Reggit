<?php

namespace Tests\Unit\UseCases\Order;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Services\Member\CurrentMemberPrincipal;
use App\Services\OrderCancellationReverser;
use App\Services\Payment\UnpaidPaymentSessionCanceller;
use App\Services\UnpaidOrderCancellationService;
use App\UseCases\Payment\OrderPaymentSessionService;
use App\UseCases\Payment\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class OrderCancelUnpaidUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function pendingOrder(User $user, ?array $session): Order
    {
        $order = Order::create([
            'project_id' => $user->project_id, 'user_id' => $user->id, 'total_amount' => 1000,
            'status' => OrderStatus::PENDING, 'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0, 'points_earned' => 0, 'order_type' => 'mobile',
        ]);
        $order->payment_session = $session;
        $order->save();

        return $order;
    }

    public function test_remote_session_is_stopped_before_cancellation_side_effects(): void
    {
        $user = User::factory()->create(['project_id' => Project::factory()->create()->id]);
        $order = $this->pendingOrder($user, ['channel' => 'checkout_web', 'result' => ['id' => 'cs_fixture']]);
        $gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $gateway);
        $service = Mockery::mock(OrderCancellationReverser::class);
        $service->shouldReceive('handleOrderCancellation')->once()->andReturnUsing(function () use ($gateway): void {
            $this->assertSame([['channel' => 'checkout_web', 'sessionId' => 'cs_fixture']], $gateway->cancelledSessions);
        });
        $useCase = new UnpaidOrderCancellationService(app(OrderRepository::class), $service, app(UnpaidPaymentSessionCanceller::class));
        $useCase->cancel($order->id, (int) $user->project_id, (int) $user->id);
        $this->assertSame(OrderStatus::CANCELLED, $order->fresh()->status);
        $useCase->cancel($order->id, (int) $user->project_id, (int) $user->id);
        $this->assertCount(1, $gateway->cancelledSessions);
    }

    public function test_remote_payment_or_network_failure_preserves_pending_order(): void
    {
        $user = User::factory()->create(['project_id' => Project::factory()->create()->id]);
        $order = $this->pendingOrder($user, ['channel' => 'card', 'result' => ['id' => 'pi_fixture']]);
        $gateway = Mockery::mock(PaymentGateway::class);
        $gateway->shouldReceive('cancelUnpaidSession')->once()->with('card', 'pi_fixture')->andThrow(new \RuntimeException('Stripe rejected cancellation'));
        $this->app->instance(PaymentGateway::class, $gateway);
        $service = Mockery::mock(OrderCancellationReverser::class);
        $service->shouldNotReceive('handleOrderCancellation');
        try {
            (new UnpaidOrderCancellationService(app(OrderRepository::class), $service, app(UnpaidPaymentSessionCanceller::class)))->cancel($order->id, (int) $user->project_id, (int) $user->id);
            $this->fail('Expected remote cancellation failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('Stripe rejected cancellation', $e->getMessage());
        }
        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
    }

    public function test_session_creation_in_progress_cannot_be_cancelled(): void
    {
        $user = User::factory()->create(['project_id' => Project::factory()->create()->id]);
        $order = $this->pendingOrder($user, ['channel' => 'checkout_web', 'lease' => 'fixture', 'expires_at' => now()->addMinutes(2)->toIso8601String()]);
        $service = Mockery::mock(OrderCancellationReverser::class);
        $service->shouldNotReceive('handleOrderCancellation');
        try {
            (new UnpaidOrderCancellationService(app(OrderRepository::class), $service, app(UnpaidPaymentSessionCanceller::class)))->cancel($order->id, (int) $user->project_id, (int) $user->id);
            $this->fail('Expected in-progress cancellation rejection');
        } catch (OrderOperationException) {
            $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
        }
    }

    public function test_ambiguous_creation_failure_blocks_cancellation_until_retry_recovers_session(): void
    {
        $user = User::factory()->create(['project_id' => Project::factory()->create()->id]);
        $order = $this->pendingOrder($user, null);
        $gateway = Mockery::mock(PaymentGateway::class);
        $gateway->shouldReceive('createCheckoutSession')->once()->andThrow(new \RuntimeException('Network timeout'));
        $sessions = new OrderPaymentSessionService($gateway, app(CurrentMemberPrincipal::class));
        try {
            $sessions->create($order->id, $order->project_id, $user, 'checkout_web');
            $this->fail('Expected timeout');
        } catch (\RuntimeException $e) {
            $this->assertSame('Network timeout', $e->getMessage());
        }
        $this->assertSame('checkout_web', $order->fresh()->payment_session['channel']);
        $service = Mockery::mock(OrderCancellationReverser::class);
        $service->shouldNotReceive('handleOrderCancellation');
        try {
            (new UnpaidOrderCancellationService(app(OrderRepository::class), $service, app(UnpaidPaymentSessionCanceller::class)))->cancel($order->id, (int) $user->project_id, (int) $user->id);
            $this->fail('Expected ambiguous cancellation rejection');
        } catch (OrderOperationException) {
            $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
        }
        $fake = new FakePaymentGateway;
        (new OrderPaymentSessionService($fake, app(CurrentMemberPrincipal::class)))->create($order->id, $order->project_id, $user, 'checkout_web');
        $this->assertSame('cs_test_123', $order->fresh()->payment_session['result']['id']);
    }

    public function test_handle_未決済注文をキャンセルすること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $orderService = Mockery::mock(OrderCancellationReverser::class);
        $orderService->shouldReceive('handleOrderCancellation')->once();

        $useCase = new UnpaidOrderCancellationService(
            app(OrderRepository::class),
            $orderService,
            app(UnpaidPaymentSessionCanceller::class),
        );

        $result = $useCase->cancel((int) $order->id, (int) $user->project_id, (int) $user->id);

        $this->assertSame(OrderStatus::CANCELLED, $result->status);
    }

    public function test_handle_決済済み注文は拒否されること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
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

        $useCase = new UnpaidOrderCancellationService(
            app(OrderRepository::class),
            Mockery::mock(OrderCancellationReverser::class),
            app(UnpaidPaymentSessionCanceller::class),
        );

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('決済済みまたは進行中の注文はキャンセルできません');

        $useCase->cancel((int) $order->id, (int) $user->project_id, (int) $user->id);
    }

    public function test_handle_キャンセル済みは副作用なしで返すこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CANCELLED,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => 'mobile',
        ]);

        $orderService = Mockery::mock(OrderCancellationReverser::class);
        $orderService->shouldNotReceive('handleOrderCancellation');

        $useCase = new UnpaidOrderCancellationService(
            app(OrderRepository::class),
            $orderService,
            app(UnpaidPaymentSessionCanceller::class),
        );

        $result = $useCase->cancel((int) $order->id, (int) $user->project_id, (int) $user->id);

        $this->assertSame(OrderStatus::CANCELLED, $result->status);
    }

    public function test_cancellation_side_effect_failure_rolls_back_and_can_be_retried(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $product = Product::factory()->create(['project_id' => $project->id, 'stock' => 3]);
        $order = Order::create([
            'project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 1000,
            'status' => OrderStatus::PENDING, 'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 20, 'points_earned' => 0, 'order_type' => 'mobile',
        ]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        PointTransaction::create(['project_id' => $project->id, 'member_id' => $member->id, 'type' => 'earned', 'points' => 100]);
        PointTransaction::create(['project_id' => $project->id, 'member_id' => $member->id, 'order_id' => $order->id, 'type' => 'used', 'points' => -20]);
        $member->recalcPointsFromTransactions();
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2, 'price' => 500]);
        $service = app(OrderCancellationReverser::class);
        $failingService = Mockery::mock(OrderCancellationReverser::class);
        $failingService->shouldReceive('handleOrderCancellation')->once()->andReturnUsing(function (Order $cancelled) use ($service): void {
            $service->handleOrderCancellation($cancelled);
            throw new \RuntimeException('fixture failure after stock restoration');
        });
        try {
            (new UnpaidOrderCancellationService(app(OrderRepository::class), $failingService, app(UnpaidPaymentSessionCanceller::class)))->cancel($order->id, (int) $user->project_id, (int) $user->id);
            $this->fail('Expected cancellation failure');
        } catch (\RuntimeException $e) {
            $this->assertSame('fixture failure after stock restoration', $e->getMessage());
        }
        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame(80, $member->fresh()->getDisplayPoints());
        $this->assertDatabaseMissing('point_transactions', ['order_id' => $order->id, 'type' => 'refunded']);
        $useCase = new UnpaidOrderCancellationService(app(OrderRepository::class), $service, app(UnpaidPaymentSessionCanceller::class));
        $useCase->cancel($order->id, (int) $user->project_id, (int) $user->id);
        $this->assertSame(OrderStatus::CANCELLED, $order->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(100, $member->fresh()->getDisplayPoints());
        $useCase->cancel($order->id, (int) $user->project_id, (int) $user->id);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(100, $member->fresh()->getDisplayPoints());
    }
}
