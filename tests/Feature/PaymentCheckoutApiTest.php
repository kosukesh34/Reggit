<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\UseCases\Payment\PaymentGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class PaymentCheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000042';

    public function test_device_user_can_create_checkout_for_own_order(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => self::LINE_USER_ID,
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 980,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => OrderType::MOBILE,
        ]);
        $this->app->instance(PaymentGateway::class, new FakePaymentGateway);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/payment/checkout?project_key=default', ['order_id' => $order->id])
            ->assertOk()
            ->assertJsonPath('session_id', 'pi_test_123');
        $url = $response->json('checkout_url');
        $this->assertStringNotContainsString('cs_test_123', $url);
        $this->get($url)->assertOk()->assertSee('カードでお支払い')->assertDontSee('type="email"', false);
        $this->get(str_replace('signature=', 'signature=invalid', $url))->assertForbidden();
        $this->get(str_replace('/card/'.$order->id, '/card/'.($order->id + 1), $url))->assertForbidden();
        $this->travel(2)->days();
        $this->get($url)->assertForbidden();
        $this->travelBack();

    }

    public function test_payment_return_redirects_to_native_app(): void
    {
        $this->get('/mobile/payment/return?status=success&order_id=42')
            ->assertOk()
            ->assertViewHas('returnUrl', 'reggit://payment?status=success&order_id=42')
            ->assertSee('window.location.replace', false)
            ->assertSee('注文画面へ戻る')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_invalid_native_return_does_not_open_an_empty_order(): void
    {
        $this->get('/mobile/payment/return?status=success&order_id=0')->assertStatus(400);
    }

    public function test_web_return_restores_the_same_project_and_order(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'total_amount' => 980, 'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0, 'points_earned' => 0, 'order_type' => OrderType::MOBILE,
        ]);
        $this->get('/mobile/payment/return?'.http_build_query([
            'surface' => 'web', 'project_key' => $project->project_key,
            'order_id' => $order->id, 'status' => 'success',
        ]))->assertOk()
            ->assertViewHas('returnUrl', url('/liff/'.$project->project_key).'?payment_order_id='.$order->id.'&payment_status=success#/menu')
            ->assertSee('window.location.replace', false);
    }

    public function test_web_return_for_shipping_order_goes_back_to_shop(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'total_amount' => 1480, 'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0, 'points_earned' => 0, 'order_type' => OrderType::SHIPPING,
        ]);
        $query = ['surface' => 'web', 'project_key' => $project->project_key, 'order_id' => $order->id, 'status' => 'success'];

        $this->get('/mobile/payment/return?'.http_build_query($query))->assertOk()
            ->assertViewHas('returnUrl', route('shop.show', ['project_key' => $project->project_key]).'?payment_order_id='.$order->id.'&payment_status=success');

        $project->update(['custom_shop_domain' => 'shop.example.com']);
        // 独自ドメインがあっても /web/{key}/shop で購入した場合はそちらへ戻す
        $this->get('/mobile/payment/return?'.http_build_query($query))->assertOk()
            ->assertViewHas('returnUrl', route('shop.show', ['project_key' => $project->project_key]).'?payment_order_id='.$order->id.'&payment_status=success');
        $this->get('https://shop.example.com/mobile/payment/return?'.http_build_query($query))->assertOk()
            ->assertViewHas('returnUrl', 'https://shop.example.com/shop?payment_order_id='.$order->id.'&payment_status=success');
    }

    public function test_shipping_order_can_checkout_on_custom_domain_and_confirm(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $project->update(['custom_shop_domain' => 'shop.example.com']);
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => self::LINE_USER_ID]);
        $order = Order::create([
            'project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 1480,
            'status' => OrderStatus::PENDING, 'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0, 'points_earned' => 0, 'order_type' => OrderType::SHIPPING,
        ]);
        $gateway = new FakePaymentGateway;
        $gateway->intent = [
            'status' => 'succeeded', 'amount' => 1480, 'currency' => 'jpy',
            'metadata' => ['order_id' => $order->id],
        ];
        $this->app->instance(PaymentGateway::class, $gateway);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('https://shop.example.com/api/payment/checkout?project_key=default', ['order_id' => $order->id, 'surface' => 'web'])
            ->assertOk();
        $this->assertStringStartsWith('https://shop.example.com/mobile/payment/return?', $gateway->checkoutCreated['successUrl']);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/payment/checkout/confirm?project_key=default', ['order_id' => $order->id])
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertSame(OrderStatus::CONFIRMED, $order->fresh()->status);
    }

    public static function paymentChannels(): array
    {
        return [['checkout'], ['card']];
    }

    #[DataProvider('paymentChannels')]
    public function test_device_user_can_confirm_paid_checkout_and_receive_order_number(string $channel): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => self::LINE_USER_ID]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 980,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => OrderType::MOBILE,
        ]);
        $order->payment_session = ['channel' => $channel, 'result' => ['id' => $channel === 'card' ? 'pi_test_123' : 'cs_test_123']];
        $order->save();
        $gateway = new FakePaymentGateway;
        $gateway->intent = [
            'status' => 'succeeded', 'amount' => 980, 'currency' => 'jpy',
            'metadata' => ['order_id' => $order->id],
        ];
        $this->app->instance(PaymentGateway::class, $gateway);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/payment/checkout/confirm?project_key=default', ['order_id' => $order->id])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNotSame(OrderStatus::PENDING, $order->fresh()->status);
        $this->assertNotEmpty($order->fresh()->order_number);
    }

    #[DataProvider('paymentChannels')]
    public function test_unpaid_checkout_does_not_confirm_order(string $channel): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => self::LINE_USER_ID]);
        $order = Order::create([
            'project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 980,
            'status' => OrderStatus::PENDING, 'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0, 'points_earned' => 0, 'order_type' => OrderType::MOBILE,
        ]);
        $order->payment_session = ['channel' => $channel, 'result' => ['id' => $channel === 'card' ? 'pi_test_123' : 'cs_test_123']];
        $order->save();
        $gateway = new FakePaymentGateway;
        $gateway->checkoutSession = ['payment_status' => 'unpaid', 'payment_intent_id' => 'pi_test_123'];
        $gateway->intent = ['status' => 'requires_payment_method'];
        $this->app->instance(PaymentGateway::class, $gateway);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/payment/checkout/confirm?project_key=default', ['order_id' => $order->id])
            ->assertStatus($channel === 'card' ? 400 : 409);
        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
    }
}
