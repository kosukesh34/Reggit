<?php

namespace Tests\Feature;

use App\Domain\Order\Events\OrderConfirmed;
use App\Domain\Order\Exceptions\OrderOperationException;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Mail\ShopOrderMail;
use App\Models\AdminUser;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\Settings\ProjectPaymentSetting;
use App\Models\User;
use App\Services\OrderNotificationOutbox;
use App\Services\OrderService;
use App\UseCases\Payment\PaymentGateway;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\Support\ConfiguresShopLegalNotice;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class ShopOperationsTest extends TestCase
{
    use ConfiguresShopLegalNotice;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    /** @return array{Project, User, Product} */
    private function shop(bool $legal = true): array
    {
        Mail::fake();
        $project = Project::factory()->create(['is_active' => true]);
        ProjectPaymentSetting::create([
            'project_id' => $project->id, 'account_id' => 'acct_test', 'publishable_key' => 'pk_test',
            'secret_key' => 'sk_test', 'webhook_secret' => 'whsec_test', 'methods' => ['card'],
        ]);
        ShopSettingStore::setValue('feature_shop_enabled', true, 'boolean', $project->id);
        ShopSettingStore::setValue('shop_shipping_fee', 800, 'integer', $project->id);
        if ($legal) {
            $this->completeShopLegalNotice($project->id);
        }
        $user = User::factory()->create(['project_id' => $project->id, 'email' => 'buyer@example.com', 'email_verified_at' => now()]);
        $product = Product::factory()->create(['project_id' => $project->id, 'name' => '焼き菓子セット', 'price' => 2000, 'stock' => 10, 'tax_rate' => 8]);

        return [$project, $user, $product];
    }

    /** @param array<string, mixed> $extra */
    private function order(User $user, Product $product, int $quantity = 1, array $extra = []): Order
    {
        return app(OrderService::class)->createOrder([
            'order_type' => OrderType::SHIPPING, 'payment_method' => PaymentMethod::STRIPE, 'payment_brand' => 'card',
            'items' => [['product_id' => $product->id, 'quantity' => $quantity]],
            'shipping_name' => '配送太郎', 'shipping_phone' => '09011112222', 'shipping_postal_code' => '1000001',
            'shipping_prefecture' => '東京都', 'shipping_address' => '千代田区1-1',
        ] + $extra, $user);
    }

    private function markPaid(Order $order): void
    {
        $order->forceFill([
            'status' => OrderStatus::CONFIRMED,
            'payment_session' => ['channel' => 'checkout_web', 'result' => ['id' => 'cs_test_1']],
        ])->save();
    }

    public function test_特定商取引法の表記が未入力なら販売せず表記ページで案内すること(): void
    {
        [$project, $user, $product] = $this->shop(legal: false);

        try {
            $this->order($user, $product);
            $this->fail('表記なしで注文できてしまう');
        } catch (OrderOperationException $e) {
            $this->assertStringContainsString('特定商取引法', $e->getMessage());
        }
        $this->get(route('shop.legal', ['project_key' => $project->project_key]))->assertOk()->assertSee('表記を準備中です');

        $this->completeShopLegalNotice($project->id);
        $this->get(route('shop.legal', ['project_key' => $project->project_key]))->assertOk()
            ->assertSee('株式会社テスト')->assertSee('返品・交換・キャンセル')->assertDontSee('表記を準備中です');
        $this->assertSame(OrderStatus::PENDING, $this->order($user, $product)->status);
    }

    public function test_送料無料ラインとお届け日時を検証して保存すること(): void
    {
        [$project, $user, $product] = $this->shop();
        ShopSettingStore::setValue('shop_free_shipping_threshold', 4000, 'integer', $project->id);
        ShopSettingStore::setValue('shop_delivery_date_enabled', true, 'boolean', $project->id);
        ShopSettingStore::setValue('shop_delivery_lead_days', 2, 'integer', $project->id);
        ShopSettingStore::setValue('shop_delivery_range_days', 7, 'integer', $project->id);
        ShopSettingStore::setValue('shop_delivery_time_slots', ['午前中', '14〜16時'], 'json', $project->id);

        $this->assertSame(800, (int) $this->order($user, $product)->shipping_fee);
        $order = $this->order($user, $product, 2, ['delivery_date' => today()->addDays(2)->toDateString(), 'delivery_time_slot' => '午前中']);
        $this->assertSame(0, (int) $order->shipping_fee);
        $this->assertSame(4000.0, (float) $order->total_amount);
        $this->assertSame(today()->addDays(2)->toDateString(), $order->delivery_date->toDateString());
        $this->assertSame('午前中', $order->delivery_time_slot);
        $this->assertSame(8, (int) $order->items()->value('tax_rate'));

        foreach ([['delivery_date' => today()->addDay()->toDateString()], ['delivery_date' => today()->addDays(9)->toDateString()], ['delivery_time_slot' => '深夜']] as $invalid) {
            try {
                $this->order($user, $product, 1, $invalid);
                $this->fail('範囲外のお届け指定を受け付けてしまう: '.json_encode($invalid));
            } catch (OrderOperationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_注文確認と発送をメールで知らせ領収書を署名付きリンクで開けること(): void
    {
        [$project, $user, $product] = $this->shop();
        ShopSettingStore::setValue('shop_invoice_registration_number', 'T1234567890123', 'string', $project->id);
        $order = $this->order($user, $product);
        $this->markPaid($order);
        $outbox = app(OrderNotificationOutbox::class);
        $outbox->record(new OrderConfirmed((int) $order->id, (int) $project->id, (int) $user->id));
        $this->assertDatabaseHas('order_notification_outbox', ['order_id' => $order->id, 'event' => 'confirmed', 'channel' => 'email']);
        $this->assertDatabaseMissing('order_notification_outbox', ['order_id' => $order->id, 'channel' => 'kitchen']);
        $outbox->drain();
        Mail::assertSent(ShopOrderMail::class, fn (ShopOrderMail $mail): bool => $mail->hasTo('buyer@example.com') && $mail->event === 'confirmed');

        /** @var AdminUser $admin */
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $order->forceFill(['status' => OrderStatus::PREPARING])->save();
        $this->actingAs($admin, 'admin')->post(route('admin.shop.orders.status', ['project_key' => $project->project_key, 'id' => $order->id]), [
            'status' => 'shipped', 'shipping_carrier' => 'yamato', 'shipping_tracking_number' => '1234-5678-9012',
        ])->assertSessionHasNoErrors();
        $outbox->drain();
        Mail::assertSent(ShopOrderMail::class, fn (ShopOrderMail $mail): bool => $mail->event === 'shipped'
            && $mail->trackingUrl === 'https://jizen.kuronekoyamato.co.jp/jizen/servlet/crjz.b.NQ0010?id=123456789012');

        $receipt = URL::signedRoute('shop.receipt', ['project_key' => $project->project_key, 'order' => $order->id]);
        $this->get($receipt)->assertOk()->assertSee('領収書')->assertSee('T1234567890123')->assertSee('8%対象')->assertSee('10%対象')
            ->assertSee('株式会社テスト');
        $this->get(route('shop.receipt', ['project_key' => $project->project_key, 'order' => $order->id]))->assertForbidden();
    }

    public function test_全額返金で注文をキャンセルし在庫を戻して知らせること(): void
    {
        [$project, $user, $product] = $this->shop();
        $fake = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $fake);
        $order = $this->order($user, $product, 2);
        $this->markPaid($order);
        $this->assertSame(8, $product->fresh()->stock);
        /** @var AdminUser $admin */
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $refund = route('admin.shop.orders.refund', ['project_key' => $project->project_key, 'id' => $order->id]);

        $this->actingAs($admin, 'admin')->post($refund)->assertSessionHasNoErrors();
        $this->actingAs($admin, 'admin')->post($refund)->assertSessionHasErrors('status');

        $this->assertSame([['paymentIntentId' => 'pi_test_123', 'amount' => 4800, 'idempotencyKey' => 'reggit-order-'.$order->id.'-refund']], $fake->refunds);
        $order->refresh();
        $this->assertSame(OrderStatus::CANCELLED, $order->status);
        $this->assertSame(4800, $order->refund_amount);
        $this->assertNotNull($order->refunded_at);
        $this->assertSame(10, $product->fresh()->stock);
        Mail::assertSent(ShopOrderMail::class, fn (ShopOrderMail $mail): bool => $mail->event === 'refunded');
    }

    public function test_公開中のショップは特定商取引法の表記と登録番号の形式を検証すること(): void
    {
        [$project] = $this->shop(legal: false);
        /** @var AdminUser $admin */
        $admin = AdminUser::factory()->create(['role' => AdminUser::ROLE_ADMIN, 'project_id' => $project->id]);
        $update = route('admin.settings.shop.update', ['project_key' => $project->project_key]);

        $this->actingAs($admin, 'admin')->post($update, ['feature_shop_enabled' => '1', 'shop_shipping_fee' => 800, 'shop_invoice_registration_number' => '123'])
            ->assertSessionHasErrors(['shop_legal_notice.seller', 'shop_invoice_registration_number']);
        $this->actingAs($admin, 'admin')->post($update, [
            'feature_shop_enabled' => '1', 'shop_shipping_fee' => 800, 'shop_legal_notice' => $this->shopLegalNotice(),
            'shop_free_shipping_threshold' => 5000, 'shop_delivery_date_enabled' => '1', 'shop_delivery_time_slots' => "午前中\n14〜16時\n\n午前中",
            'shop_invoice_registration_number' => 'T1234567890123',
        ])->assertSessionHasNoErrors();

        $this->assertSame('株式会社テスト', ShopSettingStore::getValue('shop_legal_notice', [], $project->id)['seller']);
        $this->assertSame(5000, (int) ShopSettingStore::getValue('shop_free_shipping_threshold', null, $project->id));
        $this->assertSame(['午前中', '14〜16時'], ShopSettingStore::getValue('shop_delivery_time_slots', [], $project->id));
        $this->actingAs($admin, 'admin')->get(route('admin.settings.shop', ['project_key' => $project->project_key]))->assertOk()
            ->assertSee('特定商取引法に基づく表記')->assertSee('value="T1234567890123"', false);
    }
}
