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

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/payment/checkout?project_key=default', ['order_id' => $order->id])
            ->assertOk()
            ->assertJsonPath('checkout_url', 'https://checkout.stripe.test/session')
            ->assertJsonPath('session_id', 'cs_test_123');
    }

    public function test_payment_return_redirects_to_native_app(): void
    {
        $this->get('/mobile/payment/return?status=success&order_id=42')
            ->assertRedirect('reggit://payment?status=success&order_id=42');
    }

    public function test_device_user_can_confirm_paid_checkout_and_receive_order_number(): void
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
        $order->payment_session = ['channel' => 'checkout', 'result' => ['id' => 'cs_test_123']];
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

    public function test_unpaid_checkout_does_not_confirm_order(): void
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
        $order->payment_session = ['channel' => 'checkout', 'result' => ['id' => 'cs_test_123']];
        $order->save();
        $gateway = new FakePaymentGateway;
        $gateway->checkoutSession = ['payment_status' => 'unpaid', 'payment_intent_id' => 'pi_test_123'];
        $this->app->instance(PaymentGateway::class, $gateway);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/payment/checkout/confirm?project_key=default', ['order_id' => $order->id])
            ->assertStatus(409);
        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
    }
}
