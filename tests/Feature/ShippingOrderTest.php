<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Product;
use App\Models\Project;
use App\Models\Settings\ProjectPaymentSetting;
use App\Models\User;
use App\Services\OrderService;
use App\Services\OrderStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\ConfiguresShopLegalNotice;
use Tests\TestCase;

class ShippingOrderTest extends TestCase
{
    use ConfiguresShopLegalNotice;
    use RefreshDatabase;

    public function test_配送注文は送料込みで作成でき発送済みに進められること(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        ProjectPaymentSetting::create([
            'project_id' => $project->id,
            'account_id' => 'acct_test',
            'publishable_key' => 'pk_test',
            'secret_key' => 'sk_test',
            'webhook_secret' => 'whsec_test',
            'methods' => ['card'],
        ]);
        app(ShopSettingStore::class)->setValue('feature_shop_enabled', true, 'boolean', $project->id);
        $this->completeShopLegalNotice($project->id);
        app(ShopSettingStore::class)->setValue('shop_shipping_fee', 300, 'integer', $project->id);
        app(ShopSettingStore::class)->setValue('shop_shipping_fees_by_prefecture', [
            '東京都' => 300,
            '沖縄県' => 1200,
        ], 'json', $project->id);

        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U'.str_repeat('a', 32),
        ]);
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'price' => 1000,
            'is_available' => true,
            'stock' => 10,
        ]);

        $order = app(OrderService::class)->createOrder([
            'order_type' => OrderType::SHIPPING,
            'payment_method' => PaymentMethod::STRIPE,
            'payment_brand' => 'card',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'shipping_name' => '配送太郎',
            'shipping_phone' => '09011112222',
            'shipping_postal_code' => '1000001',
            'shipping_prefecture' => '東京都',
            'shipping_address' => '千代田区1-1',
        ], $user);

        $this->assertSame(OrderType::SHIPPING, $order->order_type);
        $this->assertSame(1300.0, (float) $order->total_amount);
        $this->assertSame(300, (int) $order->shipping_fee);
        $this->assertSame('東京都', $order->shipping_prefecture);
        $this->assertSame('配送太郎', $order->shipping_name);

        $okinawa = app(OrderService::class)->createOrder([
            'order_type' => OrderType::SHIPPING,
            'payment_method' => PaymentMethod::STRIPE,
            'payment_brand' => 'card',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'shipping_name' => '配送花子',
            'shipping_phone' => '09033334444',
            'shipping_postal_code' => '9000001',
            'shipping_prefecture' => '沖縄県',
            'shipping_address' => '那覇市1-1',
        ], $user);
        $this->assertSame(2200.0, (float) $okinawa->total_amount);
        $this->assertSame(1200, (int) $okinawa->shipping_fee);

        $order->status = OrderStatus::CONFIRMED;
        $order->save();

        app(OrderStatusService::class)->update((int) $order->id, OrderStatus::PREPARING);
        app(OrderStatusService::class)->update((int) $order->id, OrderStatus::SHIPPED);

        $this->assertSame(OrderStatus::SHIPPED, $order->fresh()->status);
        foreach (['native', 'line'] as $channel) {
            $this->assertDatabaseHas('order_notification_outbox', [
                'order_id' => $order->id, 'event' => 'shipped', 'channel' => $channel,
            ]);
        }
    }
}
