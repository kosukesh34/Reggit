<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\MemberTicket;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\Settings\ProjectPaymentSetting;
use App\Models\Store;
use App\Models\User;
use App\Services\Subscription\SubscriptionGateway;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000002';

    /** @var array{checkout: list<array<string, mixed>>, cancelled: list<string>} */
    private array $calls = ['checkout' => [], 'cancelled' => []];

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $calls = &$this->calls;
        $this->app->instance(SubscriptionGateway::class, new class($calls) implements SubscriptionGateway
        {
            /** @param array{checkout: list<array<string, mixed>>, cancelled: list<string>} $calls */
            public function __construct(private array &$calls) {}

            public function createCheckout(ProjectPaymentSetting $setting, string $name, int $monthlyAmount, array $metadata, string $successUrl, string $cancelUrl): string
            {
                $this->calls['checkout'][] = compact('name', 'monthlyAmount', 'metadata', 'successUrl');

                return 'https://checkout.stripe.test/subscription';
            }

            public function cancelAtPeriodEnd(ProjectPaymentSetting $setting, string $subscriptionId): void
            {
                $this->calls['cancelled'][] = $subscriptionId;
            }
        });
    }

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    /** @return array{Project, ProjectPaymentSetting, Member, Product} */
    private function setUpShop(): array
    {
        $project = Project::factory()->create(['is_active' => true]);
        ShopSettingStore::setValue('feature_tickets_enabled', true, 'boolean', $project->id);
        $setting = ProjectPaymentSetting::create([
            'project_id' => $project->id, 'account_id' => 'acct_fixture',
            'publishable_key' => 'pk_test_fixture', 'secret_key' => 'sk_test_fixture',
            'webhook_secret' => 'whsec_fixture', 'methods' => ['card'],
        ]);
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => self::LINE_USER_ID]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $product = Product::factory()->create([
            'project_id' => $project->id, 'name' => 'コーヒー定期便', 'price' => 3000, 'stock' => null,
            'subscription_enabled' => true, 'subscription_monthly_uses' => null, 'daily_limit' => 1,
        ]);

        return [$project, $setting, $member, $product];
    }

    /** @param array<string, mixed> $object */
    private function webhook(Project $project, ProjectPaymentSetting $setting, string $type, array $object): TestResponse
    {
        $payload = json_encode(['id' => 'evt_'.uniqid(), 'object' => 'event', 'type' => $type, 'data' => ['object' => $object]], JSON_THROW_ON_ERROR);
        $timestamp = time();

        return $this->call('POST', route('payment.project.webhook', ['project_key' => $project->project_key, 'setting' => $setting->id]), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_fixture'),
        ], $payload);
    }

    /** @param array<string, string> $metadata */
    private function invoicePaid(Project $project, ProjectPaymentSetting $setting, string $invoiceId, array $metadata, int $periodEnd): TestResponse
    {
        return $this->webhook($project, $setting, 'invoice.paid', [
            'id' => $invoiceId, 'object' => 'invoice', 'currency' => 'jpy', 'amount_paid' => 3000,
            'parent' => ['type' => 'subscription_details', 'subscription_details' => ['subscription' => 'sub_fixture', 'metadata' => $metadata]],
            'lines' => ['object' => 'list', 'data' => [['id' => 'il_1', 'object' => 'line_item', 'period' => ['start' => time(), 'end' => $periodEnd]]]],
        ]);
    }

    public function test_申込から毎月の支払いで注文と当月の利用券が1回ずつ発行されること(): void
    {
        [$project, $setting, $member, $product] = $this->setUpShop();
        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];
        $query = '?project_key='.$project->project_key;

        $this->withHeaders($headers)->postJson('/api/member-subscriptions/checkout'.$query, ['product_id' => $product->id])
            ->assertOk()->assertJsonPath('data.checkout_url', 'https://checkout.stripe.test/subscription');
        $metadata = $this->calls['checkout'][0]['metadata'];
        $this->assertSame((string) $member->id, $metadata['member_id']);
        $this->assertSame(3000, $this->calls['checkout'][0]['monthlyAmount']);

        $firstEnd = now()->addMonth()->timestamp;
        $this->invoicePaid($project, $setting, 'in_1', $metadata, $firstEnd)->assertOk();
        $this->invoicePaid($project, $setting, 'in_1', $metadata, $firstEnd)->assertOk();
        $this->invoicePaid($project, $setting, 'in_2', $metadata, now()->addMonths(2)->timestamp)->assertOk();

        $orders = Order::withoutGlobalScopes()->where('order_type', OrderType::SUBSCRIPTION)->orderBy('id')->get();
        $this->assertCount(2, $orders);
        $this->assertSame(OrderStatus::COMPLETED, $orders[0]->status);
        $this->assertTrue((bool) $orders[0]->smaregi_create_pending);
        $this->assertSame((int) $metadata['store_id'], (int) $orders[0]->store_id);
        $this->assertDatabaseMissing('member_visits', ['order_id' => $orders[0]->id]);
        $subscription = MemberSubscription::withoutGlobalScopes()->sole();
        $this->assertSame('active', $subscription->status);
        $tickets = MemberTicket::withoutGlobalScopes()->where('member_subscription_id', $subscription->id)->get();
        $this->assertCount(2, $tickets);
        $this->assertNull($tickets[0]->total_uses);
        $this->assertSame($firstEnd, $tickets[0]->expires_at->timestamp);

        // 申込済みの商品は重ねて申し込めない
        $this->withHeaders($headers)->postJson('/api/member-subscriptions/checkout'.$query, ['product_id' => $product->id])
            ->assertStatus(422)->assertJsonPath('message', 'このサブスクはすでに申し込み済みです');
    }

    public function test_無制限でも1日の上限を超えて使えないこと(): void
    {
        [$project, $setting, $member, $product] = $this->setUpShop();
        $this->invoicePaid($project, $setting, 'in_1', [
            'project_id' => (string) $project->id, 'payment_setting_id' => (string) $setting->id,
            'member_id' => (string) $member->id, 'product_id' => (string) $product->id,
            'store_id' => (string) Store::where('project_id', $project->id)->value('id'),
        ], now()->addMonth()->timestamp)->assertOk();
        $ticket = MemberTicket::withoutGlobalScopes()->sole();
        $use = '/api/member-tickets/'.$ticket->id.'/use?project_key='.$project->project_key;
        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];

        $this->withHeaders($headers)->get('/api/member-tickets?project_key='.$project->project_key)->assertOk()
            ->assertJsonPath('subscriptions.0.name', 'コーヒー定期便')
            ->assertJsonPath('tickets.0.remaining_uses', null)
            ->assertJsonPath('plans.0.kind', 'subscription');
        $this->withHeaders($headers)->post($use)->assertOk()->assertJsonPath('data.status', 'active');
        $this->withHeaders($headers)->post($use)->assertStatus(422)->assertJsonPath('message', '本日の利用上限に達しました。明日以降にご利用ください');

        $this->travel(1)->days();
        $this->withHeaders($headers)->post($use)->assertOk();
    }

    public function test_他のプロジェクトの決済設定のイベントでは発行しないこと(): void
    {
        [$project, $setting, $member, $product] = $this->setUpShop();
        $this->invoicePaid($project, $setting, 'in_1', [
            'project_id' => (string) $project->id, 'payment_setting_id' => (string) ($setting->id + 1),
            'member_id' => (string) $member->id, 'product_id' => (string) $product->id, 'store_id' => '1',
        ], now()->addMonth()->timestamp)->assertOk();

        $this->assertSame(0, MemberSubscription::withoutGlobalScopes()->count());
        $this->assertSame(0, Order::withoutGlobalScopes()->count());
    }

    public function test_解約は期間末で予約され_stripeの状態変更で終了すること(): void
    {
        [$project, $setting, $member, $product] = $this->setUpShop();
        $subscription = MemberSubscription::withoutGlobalScopes()->create([
            'project_id' => $project->id, 'member_id' => $member->id, 'product_id' => $product->id,
            'payment_setting_id' => $setting->id, 'stripe_subscription_id' => 'sub_fixture',
            'name' => 'コーヒー定期便', 'status' => 'active',
        ]);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->postJson('/api/member-subscriptions/'.$subscription->id.'/cancel?project_key='.$project->project_key)
            ->assertOk()->assertJsonPath('data.cancel_at_period_end', true);
        $this->assertSame(['sub_fixture'], $this->calls['cancelled']);

        $this->webhook($project, $setting, 'customer.subscription.deleted', [
            'id' => 'sub_fixture', 'object' => 'subscription', 'status' => 'canceled', 'cancel_at_period_end' => false,
            'items' => ['object' => 'list', 'data' => []],
        ])->assertOk();
        $this->assertTrue($subscription->fresh()->isEnded());
    }

    public function test_管理画面から解約でき通常の注文では申し込めないこと(): void
    {
        [$project, $setting, $member, $product] = $this->setUpShop();
        $subscription = MemberSubscription::withoutGlobalScopes()->create([
            'project_id' => $project->id, 'member_id' => $member->id, 'product_id' => $product->id,
            'payment_setting_id' => $setting->id, 'stripe_subscription_id' => 'sub_admin',
            'name' => 'コーヒー定期便', 'status' => 'active',
        ]);
        /** @var AdminUser $admin */
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);

        $this->actingAs($admin, 'admin')->get(route('admin.members.detail', ['project_key' => $project->project_key, 'id' => $member->id]))
            ->assertOk()->assertSee('コーヒー定期便')->assertSee('契約中');
        $this->actingAs($admin, 'admin')->post(route('admin.members.subscriptions.cancel', [
            'project_key' => $project->project_key, 'id' => $member->id, 'subscription' => $subscription->id,
        ]))->assertRedirect();
        $this->assertTrue($subscription->fresh()->cancel_at_period_end);

        $this->actingAs($admin, 'admin')->post(route('admin.products.store', ['project_key' => $project->project_key]), [
            'name' => '両方', 'price' => 1000, 'category' => 'other', 'stock_unlimited' => '1',
            'ticket_enabled' => '1', 'ticket_uses' => 5, 'subscription_enabled' => '1',
        ])->assertSessionHasErrors(['subscription_enabled' => '回数券とサブスクは同時に設定できません。']);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->postJson('/api/orders?project_key='.$project->project_key, [
                'items' => [['product_id' => $product->id, 'quantity' => 1]], 'payment_method' => 'stripe', 'order_type' => 'mobile',
            ])->assertStatus(400)->assertJsonPath('error', 'サブスクは回数券の画面から申し込んでください');
    }
}
