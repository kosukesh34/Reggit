<?php

namespace Tests\Feature;

use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Events\OrderReady;
use App\Domain\Order\Exceptions\OrderOperationException;
use App\Domain\Reservation\Repositories\ReservationRepository;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Order;
use App\Models\Project;
use App\Models\Reservation;
use App\Models\User;
use App\Services\Admin\OrderAdminService;
use App\Services\Admin\ReservationAdminService;
use App\Services\Kitchen\KitchenOrderService;
use App\Services\Kitchen\KitchenPushService;
use App\Services\OrderNotificationOutbox;
use App\Services\OrderNotificationService;
use App\Services\ReservationService;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ReviewFixRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function order(string $status = 'pending'): Order
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        AdminContext::setCurrentProject($project);

        return Order::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'order_type' => 'mobile', 'payment_method' => 'stripe',
            'total_amount' => 1000, 'status' => $status,
        ]);
    }

    public function test_all_staff_surfaces_reject_unpaid_stripe_order_progress(): void
    {
        $order = $this->order();
        $admin = app(OrderAdminService::class);
        $kitchen = app(KitchenOrderService::class);
        foreach (['confirmed', 'preparing', 'ready', 'completed'] as $status) {
            try {
                $admin->updateStatus($order->id, $status);
                $this->fail('Unpaid order was advanced');
            } catch (OrderOperationException $exception) {
                $this->assertStringContainsString('オンライン決済', $exception->getMessage());
            }
        }
        foreach ([fn () => $admin->complete($order->id), fn () => $kitchen->updateStatus($order->project_id, $order->id, 'completed')] as $action) {
            try {
                $action();
                $this->fail('Unpaid order was completed');
            } catch (OrderOperationException $exception) {
                $this->assertSame('pending', $order->fresh()->status);
            }
        }
        $this->assertDatabaseCount('order_notification_outbox', 0);
    }

    public function test_paid_orders_cannot_be_returned_to_pending(): void
    {
        $order = $this->order('confirmed');
        $this->expectException(OrderOperationException::class);
        app(OrderAdminService::class)->updateStatus($order->id, 'pending');
    }

    public function test_stale_reservation_edit_cannot_resurrect_a_cancelled_reservation(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $reservation = Reservation::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'status' => 'pending']);
        AdminContext::setCurrentProject($project);
        $repository = app(ReservationRepository::class);
        $stale = $repository->findById($reservation->id, $project->id);
        $stale->updateDetails(now()->addDays(2), 3, 'stale edit');
        app(ReservationService::class)->cancelReservation($reservation->id, $user);
        try {
            $repository->save($stale);
            $this->fail('Stale aggregate overwrote the cancellation');
        } catch (ValidationException $exception) {
            $this->assertSame('cancelled', $reservation->fresh()->status);
            $this->assertSame(1, $reservation->fresh()->version);
        }
    }

    public function test_stale_reservation_cancellation_cannot_overwrite_admin_completion(): void
    {
        $project = Project::factory()->create();
        $reservation = Reservation::factory()->create(['project_id' => $project->id, 'status' => 'pending']);
        AdminContext::setCurrentProject($project);
        $repository = app(ReservationRepository::class);
        $stale = $repository->findById($reservation->id, $project->id);
        $stale->cancel();
        app(ReservationAdminService::class)->complete($reservation->id);
        try {
            $repository->save($stale);
            $this->fail('Stale cancellation overwrote completion');
        } catch (ValidationException $exception) {
            $this->assertSame('completed', $reservation->fresh()->status);
        }
    }

    public function test_admin_capacity_summary_matches_api_slot_counts(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setValue('reservation_time_slots', ['11:00', '12:00'], 'json', $project->id);
        ShopSettingStore::setValue('reservation_capacity_per_slot', 2, 'integer', $project->id);
        $day = now()->addDay()->startOfDay();
        foreach ([['11:00', 'pending'], ['11:00', 'cancelled'], ['11:00', 'completed'], ['12:00', 'confirmed']] as [$time, $status]) {
            Reservation::factory()->create(['project_id' => $project->id, 'reserved_at' => $day->format('Y-m-d').' '.$time, 'status' => $status]);
        }
        $summary = app(ReservationAdminService::class)->getReservationIndexPayload(null, $day->format('Y-m-d'), $project->id)['timeSlotSummary'];
        $this->assertSame([1, 1], array_column($summary, 'count'));
        $repository = app(ReservationRepository::class);
        foreach ($summary as $slot) {
            $start = $day->copy()->setTimeFromTimeString($slot['time']);
            $this->assertSame($slot['count'], $repository->countActiveInSlot($project->id, $start, $start->copy()->endOfMinute(), null));
        }
    }

    public function test_outbox_retries_only_the_failed_channel_and_restores_tenant_context(): void
    {
        $order = $this->order('confirmed');
        $notifications = \Mockery::mock(OrderNotificationService::class);
        $notifications->shouldReceive('notifyConfirmed')->once()->with($order->id, $order->project_id, 'native');
        $notifications->shouldReceive('notifyConfirmed')->once()->with($order->id, $order->project_id, 'line');
        $kitchen = \Mockery::mock(KitchenPushService::class);
        $kitchen->shouldReceive('notifyNewOrder')->once()->andThrow(new \RuntimeException('temporary outage'));
        $kitchen->shouldReceive('notifyNewOrder')->once()->andReturnNull();
        $outbox = new OrderNotificationOutbox($notifications, $kitchen, app(StoreContext::class));
        $event = new OrderConfirmed($order->id, $order->project_id, $order->user_id);
        DB::transaction(fn () => $outbox->record($event));
        $other = Project::factory()->create();
        AdminContext::setCurrentProject($other);
        $outbox->deliverEvent($event);
        $this->assertSame($other->id, AdminContext::getCurrentProjectId());
        $this->assertSame(2, DB::table('order_notification_outbox')->whereNotNull('delivered_at')->count());
        DB::table('order_notification_outbox')->whereNull('delivered_at')->update(['available_at' => now()->subSecond()]);
        $outbox->drain();
        $outbox->record($event);
        $outbox->deliverEvent($event);
        $this->assertDatabaseCount('order_notification_outbox', 3);
        $this->assertSame(3, DB::table('order_notification_outbox')->whereNotNull('delivered_at')->count());
        $this->assertSame($other->id, AdminContext::getCurrentProjectId());
    }

    #[TestWith(['confirmed', 'cancelled'])]
    #[TestWith(['confirmed', 'completed'])]
    #[TestWith(['ready', 'cancelled'])]
    #[TestWith(['ready', 'completed'])]
    public function test_outbox_finishes_obsolete_instructions_after_a_failed_delivery(string $eventName, string $finalStatus): void
    {
        $order = $this->order($eventName);
        $notifications = \Mockery::mock(OrderNotificationService::class);
        $method = $eventName === 'ready' ? 'notifyReady' : 'notifyConfirmed';
        foreach (['native', 'line'] as $channel) {
            $notifications->shouldReceive($method)->once()->with($order->id, $order->project_id, $channel)
                ->andThrow(new \RuntimeException('Provider unavailable'));
        }
        $kitchen = \Mockery::mock(KitchenPushService::class);
        if ($eventName === 'confirmed') {
            $kitchen->shouldReceive('notifyNewOrder')->once()->andThrow(new \RuntimeException('Provider unavailable'));
        } else {
            $kitchen->shouldNotReceive('notifyNewOrder');
        }
        $outbox = new OrderNotificationOutbox($notifications, $kitchen, app(StoreContext::class));
        $event = $eventName === 'ready'
            ? new OrderReady($order->id, $order->project_id, $order->user_id)
            : new OrderConfirmed($order->id, $order->project_id, $order->user_id);
        $outbox->record($event);
        $outbox->deliverEvent($event);
        $rows = $eventName === 'ready' ? 2 : 3;
        $this->assertSame($rows, DB::table('order_notification_outbox')->whereNull('delivered_at')->count());

        $order->update(['status' => $finalStatus]);
        $this->travel(61)->seconds();
        $this->assertSame($rows, $outbox->drain());
        $this->assertSame($rows, DB::table('order_notification_outbox')->whereNotNull('delivered_at')->whereNull('last_error')->count());
        $this->assertSame(0, $outbox->drain());
    }

    public function test_outbox_does_not_send_new_kitchen_order_after_it_is_ready(): void
    {
        $order = $this->order('ready');
        $notifications = \Mockery::mock(OrderNotificationService::class);
        $notifications->shouldReceive('notifyConfirmed')->twice();
        $notifications->shouldReceive('notifyReady')->twice();
        $kitchen = \Mockery::mock(KitchenPushService::class);
        $kitchen->shouldNotReceive('notifyNewOrder');
        $outbox = new OrderNotificationOutbox($notifications, $kitchen, app(StoreContext::class));
        $outbox->record(new OrderConfirmed($order->id, $order->project_id, $order->user_id));
        $outbox->record(new OrderReady($order->id, $order->project_id, $order->user_id));
        $this->assertSame(5, $outbox->drain());
        $this->assertSame(5, DB::table('order_notification_outbox')->whereNotNull('delivered_at')->count());
    }

    public function test_outbox_skips_active_leases_and_recovers_expired_worker_leases(): void
    {
        $order = $this->order('confirmed');
        $notifications = \Mockery::mock(OrderNotificationService::class);
        $notifications->shouldReceive('notifyConfirmed')->once()->with($order->id, $order->project_id, 'native');
        $notifications->shouldReceive('notifyConfirmed')->once()->with($order->id, $order->project_id, 'line');
        $kitchen = \Mockery::mock(KitchenPushService::class);
        $kitchen->shouldReceive('notifyNewOrder')->once()->with($order->id, $order->project_id);
        $outbox = new OrderNotificationOutbox($notifications, $kitchen, app(StoreContext::class));
        $outbox->record(new OrderConfirmed($order->id, $order->project_id, $order->user_id));
        DB::table('order_notification_outbox')->update(['lease' => 'other-worker', 'leased_until' => now()->addMinute()]);
        $this->assertSame(0, $outbox->drain());
        DB::table('order_notification_outbox')->update(['leased_until' => now()->subSecond()]);
        $this->assertSame(3, $outbox->drain());
        $this->assertSame(3, DB::table('order_notification_outbox')->whereNotNull('delivered_at')->count());
    }

    public function test_order_and_outbox_roll_back_together_and_committed_work_survives_without_dispatch(): void
    {
        $order = $this->order();
        $outbox = app(OrderNotificationOutbox::class);
        $event = new OrderConfirmed($order->id, $order->project_id, $order->user_id);
        try {
            DB::transaction(function () use ($order, $outbox, $event): void {
                $order->update(['status' => 'confirmed']);
                $outbox->record($event);
                throw new \RuntimeException('rollback');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('pending', $order->fresh()->status);
        }
        $this->assertDatabaseCount('order_notification_outbox', 0);
        DB::transaction(function () use ($order, $outbox, $event): void {
            $order->update(['status' => 'confirmed']);
            $outbox->record($event);
        });
        $this->assertSame(3, DB::table('order_notification_outbox')->whereNull('delivered_at')->count());
    }
}
