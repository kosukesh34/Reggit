<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\Member\CurrentMemberPrincipal;
use App\UseCases\Payment\OrderPaymentSessionService;
use App\UseCases\Payment\PaymentGateway;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class PaymentPrincipalTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['new', 'revoked'])]
    #[TestWith(['new', 'deleted'])]
    #[TestWith(['saved', 'revoked'])]
    #[TestWith(['saved', 'deleted'])]
    #[TestWith(['gateway', 'revoked'])]
    #[TestWith(['gateway', 'deleted'])]
    public function test_stale_principal_cannot_receive_payment_secrets(string $phase, string $change): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'total_amount' => 1200, 'status' => 'pending', 'payment_method' => 'stripe',
            'order_type' => 'mobile', 'points_used' => 0, 'points_earned' => 0,
            'payment_session' => $phase === 'saved'
                ? ['channel' => 'intent', 'result' => ['id' => 'pi_existing', 'client_secret' => 'existing_secret']]
                : null,
        ]);
        $invalidate = function () use ($change, $user): void {
            if ($change === 'revoked') {
                User::query()->whereKey($user->id)->increment('authentication_version');
            } else {
                $user->delete();
            }
        };
        $original = $order->payment_session;
        $gateway = Mockery::mock(PaymentGateway::class);
        if ($phase === 'gateway') {
            $gateway->shouldReceive('createPaymentIntent')->once()->andReturnUsing(function () use ($invalidate): array {
                $invalidate();
                return ['id' => 'pi_external', 'client_secret' => 'external_secret'];
            });
        } else {
            $invalidate();
            $gateway->shouldNotReceive('createPaymentIntent');
        }
        $rejected = false;
        try {
            (new OrderPaymentSessionService($gateway, app(CurrentMemberPrincipal::class)))
                ->create((int) $order->id, (int) $project->id, $user, 'intent');
        } catch (AuthenticationException $exception) {
            $rejected = true;
        }
        $this->assertTrue($rejected);
        $saved = $order->fresh()->payment_session;
        if ($phase === 'gateway') {
            $this->assertArrayNotHasKey('result', $saved);
            $this->assertTrue(now()->gte($saved['expires_at']));
        } else {
            $this->assertSame($original, $saved);
        }
    }
}
