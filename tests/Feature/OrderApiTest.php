<?php

namespace Tests\Feature;

use App\Enums\MemberVisitSource;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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
            'source' => MemberVisitSource::ORDER,
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
        $this->assertDatabaseMissing('member_visits', [
            'order_id' => $response->json('order.id'),
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

    public function test_zero_amount_stripe_order_is_rejected_without_consuming_stock_or_points(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_1, 'project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $product = Product::factory()->create(['project_id' => $project->id, 'price' => 500, 'stock' => 10]);
        $pointsBefore = $member->getDisplayPoints();
        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_1])->postJson('/api/orders?project_key=default', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => PaymentMethod::STRIPE, 'points_used' => 500, 'order_type' => OrderType::MOBILE,
        ])->assertStatus(400)->assertJson(['error' => 'オンライン決済の金額は1円以上必要です。ポイント利用額を減らしてください']);
        $this->assertDatabaseMissing('orders', ['user_id' => $user->id]);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame($pointsBefore, $member->fresh()->getDisplayPoints());
        $this->assertDatabaseMissing('point_transactions', ['member_id' => $member->id, 'type' => 'used']);
    }

    /** @return array<string, array{string, string, bool, int, int, string}> */
    public static function invalidPointOrders(): array
    {
        return [
            'mobile cash exceeds total' => [OrderType::MOBILE, PaymentMethod::CASH, true, 100, 500, 'ポイント利用額は注文金額以下にしてください'],
            'in store cash exceeds total' => [OrderType::IN_STORE, PaymentMethod::CASH, true, 100, 500, 'ポイント利用額は注文金額以下にしてください'],
            'cash points disabled' => [OrderType::MOBILE, PaymentMethod::CASH, false, 1000, 100, 'ポイントは現在利用できません'],
            'stripe points disabled' => [OrderType::MOBILE, PaymentMethod::STRIPE, false, 1000, 100, 'ポイントは現在利用できません'],
        ];
    }

    #[DataProvider('invalidPointOrders')]
    public function test_invalid_point_usage_does_not_create_order_or_change_balance_or_stock(
        string $orderType, string $paymentMethod, bool $enabled, int $price, int $pointsUsed, string $error,
    ): void {
        $project = $this->createProject();
        ShopSettingStore::setValue('use_points', $enabled, 'boolean', $project->id);
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_1, 'project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id, 'points' => 500]);
        $product = Product::factory()->create(['project_id' => $project->id, 'price' => $price, 'stock' => 10]);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_1])->postJson('/api/orders?project_key=default', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => $paymentMethod, 'points_used' => $pointsUsed, 'order_type' => $orderType,
        ])->assertStatus(400)->assertJson(['error' => $error]);

        $this->assertDatabaseMissing('orders', ['user_id' => $user->id]);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(500, $member->fresh()->getDisplayPoints());
        $this->assertDatabaseMissing('point_transactions', ['member_id' => $member->id, 'type' => 'used']);
    }

    /** @return array<string, array{string, bool, int, int}> */
    public static function validPointOrders(): array
    {
        return [
            'cash exact total' => [PaymentMethod::CASH, true, 100, 100],
            'stripe partial payment' => [PaymentMethod::STRIPE, true, 1000, 100],
            'cash with points disabled and no points used' => [PaymentMethod::CASH, false, 100, 0],
        ];
    }

    #[DataProvider('validPointOrders')]
    public function test_valid_point_usage_preserves_requested_payment(
        string $paymentMethod, bool $enabled, int $price, int $pointsUsed,
    ): void {
        $project = $this->createProject();
        ShopSettingStore::setValue('use_points', $enabled, 'boolean', $project->id);
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_1, 'project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id, 'points' => 500]);
        $product = Product::factory()->create(['project_id' => $project->id, 'price' => $price, 'stock' => 10]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_1])->postJson('/api/orders?project_key=default', [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => $paymentMethod, 'points_used' => $pointsUsed, 'order_type' => OrderType::MOBILE,
        ])->assertStatus(201);

        $this->assertDatabaseHas('orders', ['id' => $response->json('order.id'), 'total_amount' => $price - $pointsUsed, 'points_used' => $pointsUsed]);
        $this->assertSame(500 - $pointsUsed, $member->fresh()->getDisplayPoints());
        $this->assertSame(9, $product->fresh()->stock);
        if ($pointsUsed > 0) {
            $this->assertDatabaseHas('point_transactions', ['member_id' => $member->id, 'type' => 'used', 'points' => -$pointsUsed]);
        } else {
            $this->assertDatabaseMissing('point_transactions', ['member_id' => $member->id, 'type' => 'used']);
        }
    }
}
