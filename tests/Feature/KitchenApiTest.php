<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
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

    public function test_kitchen_店舗トークンで注文一覧を取得できること(): void
    {
        $project = $this->createProject();
        ShopSettingStore::setKitchenAccessToken('test-store-token', $project->id);
        $this->withHeader('X-Store-Token', 'test-store-token');
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
        ShopSettingStore::setKitchenAccessToken('test-store-token', $project->id);
        $this->withHeader('X-Store-Token', 'test-store-token');
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
        $this->getJson('/api/kitchen/missing-project/orders')
            ->assertStatus(404);
    }

    public function test_kitchen_ステータスを更新できること(): void
    {
        $project = $this->createProject();
        ShopSettingStore::setKitchenAccessToken('test-store-token', $project->id);
        $this->withHeader('X-Store-Token', 'test-store-token');
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
