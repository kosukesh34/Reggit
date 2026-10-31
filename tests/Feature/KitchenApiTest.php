<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Infrastructure\Persistence\Eloquent\Settings\LineSettingStore;
use App\Infrastructure\Persistence\Eloquent\Settings\OrderSettingStore;
use App\Models\AdminUser;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Project;
use App\Models\StoreAppCredential;
use App\Models\User;
use App\Services\Line\LineService;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KitchenApiTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function authenticate(Project $project): void
    {
        $admin = AdminUser::factory()->create(['project_id' => $project->id, 'role' => AdminUser::ROLE_ADMIN]);
        $token = $admin->createToken('test-kitchen', ['app:kitchen', 'project:'.$project->id]);
        StoreAppCredential::query()->create([
            'project_id' => $project->id, 'admin_user_id' => $admin->id, 'app' => 'kitchen',
            'personal_access_token_id' => $token->accessToken->id,
            'device_key_hash' => hash('sha256', 'test-device-key'), 'claimed_at' => now(),
        ]);
        $this->withToken($token->plainTextToken)->withHeader('X-Device-Key', 'test-device-key');
    }

    public function test_kitchen_店舗トークンで注文一覧を取得できること(): void
    {
        $project = $this->createProject();
        $this->authenticate($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1200,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'order_type' => OrderType::MOBILE,
            'order_number' => '2026072512',
            'points_used' => 0,
            'points_earned' => 0,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'project_id' => $project->id,
            'product_id' => null,
            'product_name' => 'ラテ',
            'quantity' => 2,
            'price' => 600,
            'notes' => null,
        ]);

        $this->getJson('/api/kitchen/'.$project->project_key.'/orders')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('orders.0.id', $order->id)
            ->assertJsonPath('orders.0.items.0.name', 'ラテ');
    }

    public function test_kitchen_product_nameが空でも商品名を取得できること(): void
    {
        $project = $this->createProject();
        $this->authenticate($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'name' => 'アボカドトースト',
            'is_available' => true,
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 900,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'order_type' => OrderType::MOBILE,
            'order_number' => '2026072514',
            'points_used' => 0,
            'points_earned' => 0,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'project_id' => $project->id,
            'product_id' => $product->id,
            'product_name' => null,
            'quantity' => 1,
            'price' => 900,
            'notes' => null,
        ]);

        $this->getJson('/api/kitchen/'.$project->project_key.'/orders')
            ->assertOk()
            ->assertJsonPath('orders.0.items.0.name', 'アボカドトースト');
    }

    public function test_kitchen_存在しないproject_keyは404になること(): void
    {
        $project = $this->createProject();
        $this->authenticate($project);
        $this->getJson('/api/kitchen/missing-project/orders')
            ->assertStatus(404);
    }

    public function test_ready_mobile_order_notifies_customer_via_line_and_push_only_once(): void
    {
        $project = $this->createProject();
        $this->authenticate($project);
        OrderSettingStore::setOrderReadyLineNotifyEnabled(true, (int) $project->id);
        LineSettingStore::setLineChannelAccessToken('test-token', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'U'.str_repeat('a', 32)]);
        $order = Order::create([
            'project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 800,
            'status' => OrderStatus::PREPARING, 'payment_method' => PaymentMethod::STRIPE,
            'order_type' => OrderType::MOBILE, 'order_number' => '2026072513',
        ]);
        $push = \Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendToUser')->once()->with(
            (int) $project->id, (int) $user->id, '受け取りの準備ができました', \Mockery::type('string'),
            \Mockery::on(fn (array $data): bool => $data['type'] === 'order_ready' && $data['tab'] === 'orders'),
        )->andReturn(['target_tokens' => 1, 'sent' => 1]);
        $line = \Mockery::mock(LineService::class);
        $line->shouldReceive('pushMessage')->once()->with($user->line_user_id, \Mockery::type('array'), 'test-token');
        $this->app->instance(PushNotificationService::class, $push);
        $this->app->instance(LineService::class, $line);
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->postJson('/api/kitchen/'.$project->project_key.'/orders/'.$order->id.'/status', ['status' => OrderStatus::READY])->assertOk();
        }
        $this->assertSame(OrderStatus::READY, $order->fresh()->status);
    }

    public function test_kitchen_ステータスを更新できること(): void
    {
        $project = $this->createProject();
        $this->authenticate($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 800,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'order_type' => OrderType::MOBILE,
            'order_number' => '2026072513',
            'points_used' => 0,
            'points_earned' => 0,
        ]);

        $this->postJson('/api/kitchen/'.$project->project_key.'/orders/'.$order->id.'/status', [
            'status' => OrderStatus::PREPARING,
        ])
            ->assertOk()
            ->assertJsonPath('order.status', OrderStatus::PREPARING);

        $this->assertSame(OrderStatus::PREPARING, $order->fresh()->status);
    }
}
