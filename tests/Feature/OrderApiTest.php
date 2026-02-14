<?php

namespace Tests\Feature;

use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Member;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_store_creates_order(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => 'line-order-1', 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $product = Product::factory()->create(['project_id' => 1, 'stock' => 10]);

        $response = $this->withHeaders(['X-Line-User-Id' => 'line-order-1'])
            ->post('/api/orders?project_key=default', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
                'payment_method' => PaymentMethod::CASH,
                'points_used' => 0,
                'order_type' => OrderType::MOBILE,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'order' => ['id', 'items']]);
    }

    public function test_index_returns_only_user_orders(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => 'line-order-2', 'project_id' => 1]);
        $other = User::factory()->create(['line_user_id' => 'line-order-3', 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $other->id, 'project_id' => 1]);
        $product = Product::factory()->create(['project_id' => 1, 'stock' => 10]);

        $this->withHeaders(['X-Line-User-Id' => 'line-order-2'])
            ->post('/api/orders?project_key=default', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => PaymentMethod::CASH,
                'points_used' => 0,
                'order_type' => OrderType::MOBILE,
            ]);

        $this->withHeaders(['X-Line-User-Id' => 'line-order-3'])
            ->post('/api/orders?project_key=default', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => PaymentMethod::CASH,
                'points_used' => 0,
                'order_type' => OrderType::MOBILE,
            ]);

        $response = $this->withHeaders(['X-Line-User-Id' => 'line-order-2'])
            ->get('/api/orders?project_key=default');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }
}
