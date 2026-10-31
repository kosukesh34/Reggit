<?php

namespace Tests\Unit\Payment;

use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\Member\CurrentMemberPrincipal;
use App\UseCases\Payment\OrderPaymentSessionService;
use App\UseCases\Payment\PaymentCreateCheckoutUseCase;
use App\UseCases\Payment\ProjectPaymentGatewayResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class PaymentCreateCheckoutUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_注文金額と復帰先を渡すこと(): void
    {
        config(['app.url' => 'https://reggit.example']);
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1480,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => OrderType::MOBILE,
        ]);
        $gateway = new FakePaymentGateway;
        $useCase = new PaymentCreateCheckoutUseCase(new OrderPaymentSessionService($gateway, app(CurrentMemberPrincipal::class), app(ProjectPaymentGatewayResolver::class)));

        $output = $useCase->handle((int) $order->id, (int) $project->id, $user);

        $this->assertStringContainsString('/mobile/payment/card/'.$order->id, $output->checkoutUrl);
        $this->assertStringContainsString('signature=', $output->checkoutUrl);
        $this->assertStringNotContainsString('cs_test_123', $output->checkoutUrl);
        $this->assertSame(1480, $gateway->created['amount']);
        $this->assertEmpty($gateway->checkoutCreated);
        $this->assertSame('card', $order->fresh()->payment_session['channel']);
        $this->travel(2)->days();
        $again = $useCase->handle((int) $order->id, (int) $project->id, $user);
        $this->assertSame($output->sessionId, $again->sessionId);
        $this->assertNotSame($output->checkoutUrl, $again->checkoutUrl);
        $this->travelBack();

    }

    public function test_existing_checkout_is_reused_without_creating_another_payment(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 1480,
            'status' => OrderStatus::PENDING, 'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0, 'points_earned' => 0, 'order_type' => OrderType::MOBILE,
        ]);
        $order->payment_session = ['channel' => 'checkout', 'result' => [
            'url' => 'https://checkout.stripe.test/existing', 'id' => 'cs_existing',
        ]];
        $order->save();
        $gateway = new FakePaymentGateway;
        $useCase = new PaymentCreateCheckoutUseCase(new OrderPaymentSessionService($gateway, app(CurrentMemberPrincipal::class), app(ProjectPaymentGatewayResolver::class)));

        $output = $useCase->handle((int) $order->id, (int) $project->id, $user);

        $this->assertSame('https://checkout.stripe.test/existing', $output->checkoutUrl);
        $this->assertSame('cs_existing', $output->sessionId);
        $this->assertEmpty($gateway->created);
        $this->assertEmpty($gateway->checkoutCreated);
    }

    public function test_checkout_未決済以外の注文は拒否すること(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1480,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => OrderType::MOBILE,
        ]);
        $gateway = new FakePaymentGateway;
        $useCase = new PaymentCreateCheckoutUseCase(new OrderPaymentSessionService($gateway, app(CurrentMemberPrincipal::class), app(ProjectPaymentGatewayResolver::class)));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('未決済の注文のみ決済できます');

        $useCase->handle((int) $order->id, (int) $project->id, $user);
    }
}
