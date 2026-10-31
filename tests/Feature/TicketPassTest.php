<?php

namespace Tests\Feature;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Member;
use App\Models\MemberTicket;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Services\OrderNumberAssigner;
use App\Services\OrderService;
use App\Services\PaidOrderRewardApplier;
use App\Services\PostCommitDomainEventDispatcher;
use App\UseCases\Payment\PaymentWebhookUseCase;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class TicketPassTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000001';

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    /** @return array{Project, User, Member, Product} */
    private function setUpTicketShop(): array
    {
        Queue::fake();
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        app(ShopSettingStore::class)->setValue('feature_tickets_enabled', true, 'boolean', $project->id);
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => self::LINE_USER_ID]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'name' => 'コーヒー11回券',
            'price' => 3000,
            'stock' => null,
            'ticket_uses' => 11,
            'ticket_valid_days' => 30,
        ]);

        return [$project, $user, $member, $product];
    }

    private function buyTicket(User $user, Product $product, int $quantity = 1): Order
    {
        return app(OrderService::class)->createOrder([
            'order_type' => OrderType::TICKET,
            'payment_method' => PaymentMethod::STRIPE,
            'payment_brand' => 'card',
            'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
        ], $user);
    }

    private function payByWebhook(Order $order): void
    {
        $gateway = new FakePaymentGateway;
        $gateway->event = [
            'type' => 'payment_intent.succeeded', 'currency' => 'jpy',
            'metadata' => ['order_id' => $order->id],
            'amount' => (int) $order->total_amount,
        ];
        (new PaymentWebhookUseCase(
            $gateway,
            app(OrderRepository::class),
            app(PostCommitDomainEventDispatcher::class),
            app(OrderNumberAssigner::class),
            app(PaidOrderRewardApplier::class),
            app(StoreContext::class),
        ))->handle('{}', 'sig', 'secret');
    }

    public function test_決済確定で回数券が1回だけ発行され注文は完了になること(): void
    {
        [, $user, $member, $product] = $this->setUpTicketShop();
        $order = $this->buyTicket($user, $product, 2);
        $this->assertSame(OrderStatus::PENDING, $order->status);
        $this->assertSame(0, MemberTicket::count());

        $this->payByWebhook($order);
        $this->payByWebhook($order);

        $this->assertSame(OrderStatus::COMPLETED, $order->fresh()->status);
        $ticket = MemberTicket::sole();
        $this->assertSame($member->id, $ticket->member_id);
        $this->assertSame(22, $ticket->total_uses);
        $this->assertSame('コーヒー11回券', $ticket->name);
        $this->assertTrue($ticket->expires_at->isSameDay(now()->addDays(30)));
        $this->assertDatabaseMissing('member_visits', ['order_id' => $order->id]);
        $this->assertTrue((bool) $order->fresh()->smaregi_create_pending);
    }

    public function test_アプリのタブは回数券とショップの機能設定に従うこと(): void
    {
        [$project] = $this->setUpTicketShop();
        app(ShopSettingStore::class)->setValue('feature_shop_enabled', false, 'boolean', $project->id);

        $keys = fn (): array => array_column($this->getJson('/api/v1/app-config?project_key=default')->assertOk()->json('navigation.hamburger'), 'key');
        $this->assertContains('tickets', $keys());
        $this->assertNotContains('shop', $keys());

        app(ShopSettingStore::class)->setValue('feature_tickets_enabled', false, 'boolean', $project->id);
        app(ShopSettingStore::class)->setValue('feature_shop_enabled', true, 'boolean', $project->id);
        $this->assertNotContains('tickets', $keys());
        $this->assertContains('shop', $keys());
    }

    public function test_管理画面で設定した1日の上限が回数券にも効くこと(): void
    {
        [$project, $user] = $this->setUpTicketShop();
        /** @var AdminUser $admin */
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $this->actingAs($admin, 'admin')->post(route('admin.products.store', ['project_key' => $project->project_key]), [
            'name' => 'ランチ5回券', 'price' => 4000, 'category' => 'other', 'stock_unlimited' => '1', 'is_available' => '1',
            'ticket_enabled' => '1', 'ticket_uses' => 5, 'daily_limit' => 1,
        ])->assertSessionHasNoErrors();
        $product = Product::where('name', 'ランチ5回券')->sole();
        $this->assertSame(1, $product->daily_limit);

        $this->payByWebhook($this->buyTicket($user, $product));
        $ticket = MemberTicket::sole();
        $this->assertSame(1, $ticket->daily_limit);
        $use = '/api/member-tickets/'.$ticket->id.'/use?project_key=default';
        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];
        $this->withHeaders($headers)->post($use)->assertOk()->assertJsonPath('data.remaining_uses', 4);
        $this->withHeaders($headers)->post($use)->assertStatus(422)->assertJsonPath('message', '本日の利用上限に達しました。明日以降にご利用ください');

        // スタッフが取り消した利用は数えない
        $this->actingAs($admin, 'admin')->post(route('admin.members.tickets.uses.cancel', [
            'project_key' => $project->project_key, 'id' => $ticket->member_id, 'ticket' => $ticket->id, 'use' => $ticket->uses()->value('id'),
        ]))->assertRedirect();
        $this->withHeaders($headers)->post($use)->assertOk();
    }

    public function test_回数券と通常商品は混ぜて注文できないこと(): void
    {
        [$project, $user, , $ticketProduct] = $this->setUpTicketShop();
        $food = Product::factory()->create(['project_id' => $project->id, 'stock' => null]);

        try {
            $this->buyTicket($user, $food);
            $this->fail('通常商品を回数券として購入できてしまう');
        } catch (OrderOperationException $e) {
            $this->assertSame('回数券以外の商品は一緒に購入できません', $e->getMessage());
        }

        $this->expectExceptionMessage('回数券は回数券の画面から購入してください');
        app(OrderService::class)->createOrder([
            'order_type' => OrderType::MOBILE,
            'payment_method' => PaymentMethod::CASH,
            'items' => [['product_id' => $ticketProduct->id, 'quantity' => 1]],
        ], $user);
    }

    public function test_機能が無効なら回数券を購入できないこと(): void
    {
        [$project, $user, , $product] = $this->setUpTicketShop();
        app(ShopSettingStore::class)->setValue('feature_tickets_enabled', false, 'boolean', $project->id);

        $this->expectExceptionMessage('回数券は現在購入できません。');
        $this->buyTicket($user, $product);
    }

    public function test_一覧と利用で残り回数が減り使い切ると拒否されること(): void
    {
        [, $user, $member, $product] = $this->setUpTicketShop();
        Product::factory()->create(['project_id' => $product->project_id, 'name' => '通常のパン', 'stock' => null]);
        $ticket = MemberTicket::create([
            'project_id' => $product->project_id, 'member_id' => $member->id,
            'order_id' => $this->buyTicket($user, $product)->id,
            'order_item_id' => Order::latest('id')->first()->items()->value('id'),
            'product_id' => $product->id, 'name' => '2回券', 'total_uses' => 2,
        ]);
        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];

        $this->withHeaders($headers)->get('/api/member-tickets?project_key=default')
            ->assertOk()
            ->assertJsonPath('plans.0.id', $product->id)
            ->assertJsonPath('tickets.0.remaining_uses', 2);
        $this->withHeaders($headers)->get('/api/products?project_key=default')
            ->assertOk()
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.name', '通常のパン');

        $use = '/api/member-tickets/'.$ticket->id.'/use?project_key=default';
        $this->withHeaders($headers)->post($use)->assertOk()->assertJsonPath('data.remaining_uses', 1);
        $this->withHeaders($headers)->post($use)->assertOk()->assertJsonPath('data.status', 'used_up');
        $this->withHeaders($headers)->post($use)->assertStatus(422)->assertJsonPath('message', 'この回数券は使い切りました');
        $this->assertSame(2, $ticket->uses()->count());
    }

    public function test_期限切れと他の会員の回数券は使えないこと(): void
    {
        [$project, $user, $member, $product] = $this->setUpTicketShop();
        $order = $this->buyTicket($user, $product);
        $expired = MemberTicket::create([
            'project_id' => $project->id, 'member_id' => $member->id, 'order_id' => $order->id,
            'order_item_id' => $order->items()->value('id'), 'name' => '期限切れ', 'total_uses' => 5,
            'expires_at' => now()->subMinute(),
        ]);
        $other = User::factory()->create(['project_id' => $project->id]);
        $otherMember = Member::factory()->create(['user_id' => $other->id, 'project_id' => $project->id]);
        $otherOrder = $this->buyTicket($other, $product);
        $othersTicket = MemberTicket::create([
            'project_id' => $project->id, 'member_id' => $otherMember->id, 'order_id' => $otherOrder->id,
            'order_item_id' => $otherOrder->items()->value('id'), 'name' => '他人の券', 'total_uses' => 5,
        ]);
        $headers = ['X-Line-User-Id' => self::LINE_USER_ID];

        $this->withHeaders($headers)->post('/api/member-tickets/'.$expired->id.'/use?project_key=default')
            ->assertStatus(422)->assertJsonPath('message', 'この回数券は有効期限が切れています');
        $this->withHeaders($headers)->post('/api/member-tickets/'.$othersTicket->id.'/use?project_key=default')
            ->assertStatus(422)->assertJsonPath('message', '回数券が見つかりません');
        $this->assertSame(0, $othersTicket->fresh()->used_count);
    }

    public function test_管理画面で利用取消と無効化ができること(): void
    {
        [$project, $user, , $product] = $this->setUpTicketShop();
        $order = $this->buyTicket($user, $product);
        $this->payByWebhook($order);
        $ticket = MemberTicket::sole();
        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/member-tickets/'.$ticket->id.'/use?project_key=default')->assertOk();
        /** @var AdminUser $admin */
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $params = ['project_key' => $project->project_key, 'id' => $ticket->member_id, 'ticket' => $ticket->id];

        $this->actingAs($admin, 'admin')->get(route('admin.members.detail', ['project_key' => $project->project_key, 'id' => $ticket->member_id]))
            ->assertOk()->assertSee('コーヒー11回券')->assertSee('10 / 11回');
        $this->actingAs($admin, 'admin')
            ->post(route('admin.members.tickets.uses.cancel', $params + ['use' => $ticket->uses()->value('id')]))
            ->assertRedirect();
        $this->assertSame(0, $ticket->fresh()->used_count);

        $this->actingAs($admin, 'admin')->post(route('admin.members.tickets.revoke', $params))->assertRedirect();
        $this->assertSame('revoked', $ticket->fresh()->status());
    }
}
