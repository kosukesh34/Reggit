<?php

namespace Tests\Feature;

use App\Enums\OrderType;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\Admin\OrderAdminService;
use App\Services\Kitchen\KitchenPushService;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrdersTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function createAdmin(Project $project): AdminUser
    {
        return AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
    }

    public function test_completing_mobile_order_sends_push_once_even_when_line_is_disabled(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        ShopSettingStore::setOrderReadyLineNotifyEnabled(false, (int) $project->id);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'order_type' => OrderType::MOBILE,
            'total_amount' => 1000,
            'status' => 'confirmed',
            'payment_method' => 'stripe',
        ]);
        $push = \Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendToUser')->once()->with(
            (int) $project->id, (int) $user->id, \Mockery::type('string'), \Mockery::type('string'),
            \Mockery::on(fn (array $data): bool => $data['type'] === 'order_completed' && $data['order_id'] === (string) $order->id),
        )->andReturn(['target_tokens' => 1, 'sent' => 1]);
        $this->app->instance(PushNotificationService::class, $push);
        $service = app(OrderAdminService::class);
        $service->complete((int) $order->id);
        $service->complete((int) $order->id);
        $this->assertSame('completed', $order->fresh()->status);
    }

    public function test_confirming_mobile_order_notifies_customer_once(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 1000,
            'order_type' => OrderType::MOBILE, 'status' => 'pending', 'payment_method' => 'stripe',
        ]);
        $push = \Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendToUser')->once()->with(
            (int) $project->id, (int) $user->id, \Mockery::type('string'), \Mockery::type('string'),
            \Mockery::on(fn (array $data): bool => $data['type'] === 'order_confirmed' && $data['tab'] === 'orders'),
        )->andReturn(['target_tokens' => 1, 'sent' => 1]);
        $kitchen = \Mockery::mock(KitchenPushService::class);
        $kitchen->shouldReceive('notifyNewOrder')->once()->with((int) $order->id, (int) $project->id);
        $this->app->instance(PushNotificationService::class, $push);
        $this->app->instance(KitchenPushService::class, $kitchen);
        $service = app(OrderAdminService::class);
        $service->updateStatus((int) $order->id, 'confirmed');
        $service->updateStatus((int) $order->id, 'confirmed');
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_orders_注文一覧にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.orders', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_orders_user_idで会員の注文だけに絞り込めること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $other = User::factory()->create(['project_id' => $project->id]);
        Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'order_number' => 'mine-order',
            'order_type' => 'take_out',
            'total_amount' => 1000,
            'status' => 'completed',
            'payment_method' => 'cash',
        ]);
        Order::create([
            'project_id' => $project->id,
            'user_id' => $other->id,
            'order_number' => 'other-order',
            'order_type' => 'take_out',
            'total_amount' => 2000,
            'status' => 'completed',
            'payment_method' => 'cash',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.orders', [
                'project_key' => $project->project_key,
                'user_id' => $user->id,
                'status' => 'all',
            ]));

        $response->assertStatus(200);
        $response->assertSee('mine-order');
        $response->assertDontSee('other-order');
        $response->assertSee('会員の絞り込みを解除');
    }
}
