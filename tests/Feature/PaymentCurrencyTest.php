<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\PaidOrderRewardApplier;
use App\UseCases\Payment\PaymentConfirmUseCase;
use App\UseCases\Payment\PaymentGateway;
use App\UseCases\Payment\PaymentWebhookUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class PaymentCurrencyTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['confirm', 'usd'])]
    #[TestWith(['confirm', null])]
    #[TestWith(['webhook', 'usd'])]
    #[TestWith(['webhook', null])]
    public function test_legacy_payment_cannot_confirm_a_different_or_missing_currency(string $path, ?string $currency): void
    {
        Queue::fake();
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'total_amount' => 1000, 'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE, 'order_type' => 'mobile',
            'points_used' => 0, 'points_earned' => 0,
        ]);
        $gateway = new FakePaymentGateway;
        $payment = ['status' => 'succeeded', 'amount' => 1000, 'metadata' => ['order_id' => $order->id]];
        if ($currency !== null) {
            $payment['currency'] = $currency;
        }
        $gateway->intent = $payment;
        $gateway->event = ['type' => 'payment_intent.succeeded'] + $payment;
        $this->app->instance(PaymentGateway::class, $gateway);
        $this->mock(PaidOrderRewardApplier::class)->shouldNotReceive('applyStripePaymentRewardsIfNeeded');

        if ($path === 'confirm') {
            $result = app(PaymentConfirmUseCase::class)->handle('pi_fixture', (int) $order->id, (int) $project->id, (int) $user->id);
            $this->assertFalse($result->isSuccess);
            $this->assertSame('決済通貨が注文と一致しません', $result->message);
        } else {
            $this->assertTrue(app(PaymentWebhookUseCase::class)->handle('{}', 'sig', 'secret'));
        }
        $fresh = $order->fresh();
        $this->assertSame(OrderStatus::PENDING, $fresh->status);
        $this->assertNull($fresh->order_number);
        $this->assertFalse($fresh->smaregi_create_pending);
        $this->assertDatabaseCount('order_notification_outbox', 0);
        Queue::assertNothingPushed();
    }
}
