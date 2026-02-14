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

class OrderApiV1Test extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_store_defaults_order_type(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\AuthenticateApiToken::class);
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => 'line-order-v1', 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $product = Product::factory()->create(['project_id' => 1, 'stock' => 10]);

        $response = $this->withHeaders(['X-Line-User-Id' => 'line-order-v1'])
            ->post('/api/v1/orders?project_key=default', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => PaymentMethod::CASH,
                'points_used' => 0,
            ]);

        $response->assertStatus(201);
        $this->assertSame(OrderType::MOBILE, $response->json('data.order.order_type'));
    }
}
