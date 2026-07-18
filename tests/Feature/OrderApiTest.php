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

    /** 有効な形式（U + 32桁 hex）でないと UserService が同一ユーザーとして扱わない */
    private const LINE_USER_1 = 'U00000000000000000000000000000001';

    private const LINE_USER_2 = 'U00000000000000000000000000000002';

    private const LINE_USER_3 = 'U00000000000000000000000000000003';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_store_注文が作成されること(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_1, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $product = Product::factory()->create(['project_id' => 1, 'stock' => 10]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_1])
            ->post('/api/orders?project_key=default', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
                'payment_method' => PaymentMethod::CASH,
                'points_used' => 0,
                'order_type' => OrderType::MOBILE,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'order' => ['id', 'items', 'order_number']]);

        $expectedStored = now()->format('Ymd').'1';
        $expectedDisplay = ((int) now()->format('j')).'1';
        $this->assertSame($expectedDisplay, $response->json('order.order_number'));
        $this->assertDatabaseHas('orders', [
            'id' => $response->json('order.id'),
            'order_number' => $expectedStored,
        ]);

        $member = Member::where('user_id', $user->id)->first();
        $this->assertNotNull($member);
        $this->assertDatabaseHas('member_visits', [
            'member_id' => $member->id,
            'source' => \App\Enums\MemberVisitSource::ORDER,
        ]);
        $member->refresh();
        $this->assertNotNull($member->last_visited_at);
    }

    public function test_store_stripe注文は作成時点で注文番号を採番しないこと(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_1, 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $product = Product::factory()->create(['project_id' => 1, 'stock' => 10]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_1])
            ->post('/api/orders?project_key=default', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => PaymentMethod::STRIPE,
                'points_used' => 0,
                'order_type' => OrderType::MOBILE,
            ]);

        $response->assertStatus(201);
        $this->assertNull($response->json('order.order_number'));
        $this->assertDatabaseHas('orders', [
            'id' => $response->json('order.id'),
            'order_number' => null,
            'status' => 'pending',
        ]);
    }

    public function test_index_自分の注文のみ返すこと(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_2, 'project_id' => $project->id]);
        $other = User::factory()->create(['line_user_id' => self::LINE_USER_3, 'project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        Member::factory()->create(['user_id' => $other->id, 'project_id' => $project->id]);
        $product = Product::factory()->create(['project_id' => $project->id, 'stock' => 10]);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_2])
            ->post('/api/orders?project_key=default', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => PaymentMethod::CASH,
                'points_used' => 0,
                'order_type' => OrderType::MOBILE,
            ]);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_3])
            ->post('/api/orders?project_key=default', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => PaymentMethod::CASH,
                'points_used' => 0,
                'order_type' => OrderType::MOBILE,
            ]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_2])
            ->get('/api/orders?project_key=default');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }
}
