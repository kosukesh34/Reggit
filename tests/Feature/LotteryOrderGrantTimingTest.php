<?php

namespace Tests\Feature;

use App\Enums\LotteryGrantTrigger;
use App\Enums\LotteryRewardType;
use App\Enums\LotterySourceType;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Coupon;
use App\Models\LotteryPrize;
use App\Models\Member;
use App\Models\MemberLotteryEntitlement;
use App\Models\Product;
use App\Models\Project;
use App\Models\Settings\ProjectLotterySetting;
use App\Models\User;
use App\Services\Admin\OrderAdminService;
use App\Services\OrderService;
use App\Services\Smaregi\SmaregiOrderTransactionOutboundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class LotteryOrderGrantTimingTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000077';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function enableGacha(Project $project): void
    {
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'is_active' => true]);
        ProjectLotterySetting::query()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'gacha_enabled' => true,
                'scratch_enabled' => false,
                'grant_trigger' => LotteryGrantTrigger::EVERY_PURCHASE,
                'min_amount' => null,
                'entitlement_expires_hours' => null,
            ]
        );
        LotteryPrize::query()->create([
            'project_id' => $project->id,
            'reward_type' => LotteryRewardType::GACHA,
            'coupon_id' => $coupon->id,
            'probability' => 100,
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }

    public function test_現金店内注文の作成時点ではガチャ権利を付与しないこと(): void
    {
        $project = $this->createProject();
        $this->enableGacha($project);
        $user = User::factory()->create([
            'line_user_id' => self::LINE_USER_ID,
            'project_id' => $project->id,
        ]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $product = Product::factory()->create(['project_id' => $project->id, 'stock' => 10, 'price' => 1000]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/orders?project_key=default', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => PaymentMethod::CASH,
                'points_used' => 0,
                'order_type' => OrderType::IN_STORE,
            ]);

        $response->assertStatus(201);
        $orderId = (int) $response->json('order.id');
        $this->assertDatabaseMissing('member_lottery_entitlements', [
            'source_type' => LotterySourceType::ORDER,
            'source_id' => (string) $orderId,
        ]);
    }

    public function test_stripe注文の作成時点ではガチャ権利を付与しないこと(): void
    {
        $project = $this->createProject();
        $this->enableGacha($project);
        $user = User::factory()->create([
            'line_user_id' => self::LINE_USER_ID,
            'project_id' => $project->id,
        ]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $product = Product::factory()->create(['project_id' => $project->id, 'stock' => 10, 'price' => 1000]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/orders?project_key=default', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 1],
                ],
                'payment_method' => PaymentMethod::STRIPE,
                'points_used' => 0,
                'order_type' => OrderType::MOBILE,
            ]);

        $response->assertStatus(201);
        $orderId = (int) $response->json('order.id');
        $this->assertDatabaseMissing('member_lottery_entitlements', [
            'source_type' => LotterySourceType::ORDER,
            'source_id' => (string) $orderId,
        ]);
    }

    public function test_現金注文の完了時にガチャ権利が付与されること(): void
    {
        $project = $this->createProject();
        $this->enableGacha($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $product = Product::factory()->create(['project_id' => $project->id, 'stock' => 10, 'price' => 1200]);

        $order = resolve(OrderService::class)->createOrder([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'payment_method' => PaymentMethod::CASH,
            'points_used' => 0,
            'order_type' => OrderType::IN_STORE,
        ], $user);

        $this->assertTrue($order->refresh()->smaregi_create_pending);
        $this->assertSame(0, MemberLotteryEntitlement::query()
            ->where('member_id', $member->id)
            ->where('source_type', LotterySourceType::ORDER)
            ->where('source_id', (string) $order->id)
            ->count());

        $smaregi = Mockery::mock(SmaregiOrderTransactionOutboundService::class);
        $smaregi->shouldReceive('finalizeCashLayawayWithRetry')->once()->andReturn(true);
        $this->app->instance(SmaregiOrderTransactionOutboundService::class, $smaregi);

        resolve(OrderAdminService::class)->complete((int) $order->id);

        $this->assertDatabaseHas('member_lottery_entitlements', [
            'member_id' => $member->id,
            'reward_type' => LotteryRewardType::GACHA,
            'source_type' => LotterySourceType::ORDER,
            'source_id' => (string) $order->id,
            'is_done' => false,
        ]);
    }

    public function test_stripe決済確定後にガチャ権利が付与されること(): void
    {
        $project = $this->createProject();
        $this->enableGacha($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $product = Product::factory()->create(['project_id' => $project->id, 'stock' => 10, 'price' => 800]);

        $order = resolve(OrderService::class)->createOrder([
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'order_type' => OrderType::MOBILE,
        ], $user);

        $this->assertSame(0, MemberLotteryEntitlement::query()
            ->where('member_id', $member->id)
            ->count());

        $order->status = OrderStatus::CONFIRMED;
        $order->save();

        resolve(OrderService::class)->applyStripePaymentRewardsIfNeeded($order->fresh());

        $this->assertDatabaseHas('member_lottery_entitlements', [
            'member_id' => $member->id,
            'reward_type' => LotteryRewardType::GACHA,
            'source_type' => LotterySourceType::ORDER,
            'source_id' => (string) $order->id,
            'is_done' => false,
        ]);
    }
}
