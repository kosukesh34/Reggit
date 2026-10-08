<?php

namespace Tests\Feature;

use App\Models\KitchenDevice;
use App\Models\Order;
use App\Models\Project;
use App\Models\Store;
use App\Models\User;
use App\Services\Kitchen\KitchenOrderService;
use App\Services\Kitchen\KitchenPushService;
use App\Services\PushNotificationService;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenStoreBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_device_registration_records_the_authenticated_store(): void
    {
        $project = Project::factory()->create();
        $store = $project->stores()->firstOrFail();
        AdminContext::setCurrentProject($project);
        $device = app(StoreContext::class)->runForStore($store,
            fn () => app(KitchenOrderService::class)->registerDevice($project->id, 'same-token'),
        );
        $this->assertSame($store->id, $device->store_id);
    }

    public function test_notifications_only_reach_the_order_store_and_skip_unassigned_devices(): void
    {
        $project = Project::factory()->create();
        $a = $project->stores()->firstOrFail();
        $b = Store::create(['project_id' => $project->id, 'store_key' => 'other', 'name' => 'Other', 'is_active' => true]);
        AdminContext::setCurrentProject($project);
        $context = app(StoreContext::class);
        $context->runForStore($a, fn () => app(KitchenOrderService::class)->registerDevice($project->id, 'token-a'));
        $context->runForStore($b, fn () => app(KitchenOrderService::class)->registerDevice($project->id, 'token-b'));
        KitchenDevice::create(['project_id' => $project->id, 'push_token' => 'legacy-token']);
        $order = $context->runForStore($a, fn () => Order::create([
            'project_id' => $project->id, 'store_id' => $a->id, 'total_amount' => 800,
            'payment_method' => 'stripe', 'order_type' => 'mobile', 'status' => 'confirmed',
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
        ]));
        $push = \Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendToSubscriptionIds')->once()->with(
            ['token-a'], '新しい注文', \Mockery::type('string'),
            \Mockery::on(fn (array $data) => $data['order_id'] === (string) $order->id),
            'kitchen-order-'.$order->id,
        )->andReturn(['sent' => 1, 'failed' => 0]);
        $this->app->instance(PushNotificationService::class, $push);
        $context->runForStore($a, fn () => app(KitchenPushService::class)->notifyNewOrder($order->id, $project->id));
        $order->update(['store_id' => null]);
        app(KitchenPushService::class)->notifyNewOrder($order->id, $project->id);
    }

    public function test_reregistration_claims_legacy_device_and_moves_an_existing_token(): void
    {
        $project = Project::factory()->create();
        $a = $project->stores()->firstOrFail();
        $b = Store::create(['project_id' => $project->id, 'store_key' => 'other', 'name' => 'Other', 'is_active' => true]);
        AdminContext::setCurrentProject($project);
        $legacy = KitchenDevice::create(['project_id' => $project->id, 'push_token' => 'same-token']);
        $context = app(StoreContext::class);
        $context->runForStore($a, fn () => app(KitchenOrderService::class)->registerDevice($project->id, 'same-token'));
        $this->assertSame($a->id, $legacy->fresh()->store_id);
        $context->runForStore($b, fn () => app(KitchenOrderService::class)->registerDevice($project->id, 'same-token'));
        $this->assertSame($b->id, $legacy->fresh()->store_id);
        $context->runForStore($a, fn () => app(KitchenOrderService::class)->unregisterDevice($project->id, 'same-token'));
        $this->assertDatabaseHas('kitchen_devices', ['id' => $legacy->id]);
        $context->runForStore($b, fn () => app(KitchenOrderService::class)->unregisterDevice($project->id, 'same-token'));
        $this->assertDatabaseMissing('kitchen_devices', ['id' => $legacy->id]);
    }
}
