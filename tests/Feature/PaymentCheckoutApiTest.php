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
}
