<?php

namespace Tests\Feature;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Member;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiPointsSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderPointsEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public static function excludedStates(): array
    {
        return [[OrderStatus::CANCELLED, PaymentMethod::STRIPE],
            [OrderStatus::CANCELLED, PaymentMethod::CASH], [OrderStatus::PENDING, PaymentMethod::STRIPE]];
    }

    #[DataProvider('excludedStates')]
    public function test_recovery_search_does_not_include_cancelled_or_unpaid_online_orders(string $status, string $payment): void
    {
        $order = $this->order($status, $payment);
        $repository = app(OrderRepository::class);
        $this->assertFalse($repository->hasUnsyncedSmaregiPointsForUser((int) $order->user_id, (int) $order->project_id));
        $this->assertSame([], $repository->findUnsyncedSmaregiPointsOrdersForUser(
            (int) $order->user_id, (int) $order->project_id,
        ));
    }

    #[DataProvider('excludedStates')]
    public function test_legacy_worker_does_not_send_excluded_order_or_mark_it_synced(string $status, string $payment): void
    {
        $order = $this->order($status, $payment);
        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldNotReceive('getContractIdForProject');
        $api->shouldNotReceive('syncCustomerPointsForProject');
        $sync = new SmaregiPointsSyncService(app(MemberRepository::class), app(OrderRepository::class), $api);
        $this->assertTrue($sync->syncOrderNetPoints((int) $order->id, (int) $order->project_id, (int) $order->user_id));
        $this->assertNull($order->refresh()->smaregi_points_synced_at);
        $this->assertNull($order->smaregi_points_sync_started_at);
    }

    public function test_cash_pending_remains_eligible_and_stripe_becomes_eligible_after_confirmation(): void
    {
        $cash = $this->order(OrderStatus::PENDING, PaymentMethod::CASH);
        $stripe = $this->order(OrderStatus::PENDING, PaymentMethod::STRIPE);
        $stripe->status = OrderStatus::CONFIRMED;
        $stripe->save();
        $repository = app(OrderRepository::class);
        foreach ([$cash, $stripe] as $order) {
            $this->assertTrue($repository->hasUnsyncedSmaregiPointsForUser((int) $order->user_id, (int) $order->project_id));
            $found = $repository->findUnsyncedSmaregiPointsOrdersForUser((int) $order->user_id, (int) $order->project_id);
            $this->assertSame((int) $order->id, $found[0]['orderId']);
        }
    }

    public function test_net_zero_orders_do_not_block_inbound_and_synced_orders_release_the_guard(): void
    {
        $order = $this->order(OrderStatus::CONFIRMED, PaymentMethod::CASH);
        $repository = app(OrderRepository::class);
        $this->assertTrue($repository->hasUnsyncedSmaregiPointsForUser((int) $order->user_id, (int) $order->project_id));
        $order->update(['points_earned' => 100]);
        $this->assertFalse($repository->hasUnsyncedSmaregiPointsForUser((int) $order->user_id, (int) $order->project_id));
        $order->update(['points_earned' => 0, 'smaregi_points_synced_at' => now()]);
        $this->assertFalse($repository->hasUnsyncedSmaregiPointsForUser((int) $order->user_id, (int) $order->project_id));
    }

    private function order(string $status, string $payment): Order
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'smaregi_customer_id' => 'cust-test']);

        return Order::create(['project_id' => $project->id, 'user_id' => $user->id,
            'status' => $status, 'payment_method' => $payment, 'order_type' => 'mobile',
            'total_amount' => 900, 'points_used' => 100, 'points_earned' => 0]);
    }
}
