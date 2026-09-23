<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\UserService;
use App\UseCases\Payment\OrderPaymentSessionService;
use App\UseCases\Payment\PaymentCreateIntentUseCase;
use App\UseCases\Payment\PaymentGateway;
use App\UseCases\Payment\PaymentSessionInProgressException;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

/** Regression coverage for tenant and identity boundaries. */
class SecurityBoundaryTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['line.verify_liff_id_token' => true]);
        $this->project = Project::factory()->create(['project_key' => 'security-review', 'is_active' => true]);
    }

    public static function queueActions(): array
    {
        return [
            'cancel' => ['cancel', 'waiting'],
            'enter' => ['enter', 'called'],
            'recall' => ['recall', 'called'],
            'call-next' => ['call-next', 'waiting'],
        ];
    }

    #[DataProvider('queueActions')]
    public function test_anonymous_client_cannot_mutate_another_customers_queue(string $action, string $status): void
    {
        $entry = QueueEntry::factory()->create([
            'project_id' => $this->project->id, 'member_id' => null, 'user_id' => null,
            'status' => $status, 'called_at' => $status === 'called' ? now() : null,
        ]);
        $path = $action === 'call-next' ? 'call-next' : $entry->id.'/'.$action;
        $response = $this->postJson('/api/queue/'.$path.'?project_key=security-review');

        $this->assertContains($response->status(), [401, 403, 404], $response->getContent());
        $this->assertSame($status, $entry->fresh()->status);
    }

    public function test_anonymous_client_cannot_obtain_another_customers_queue_token(): void
    {
        $entry = QueueEntry::factory()->create([
            'project_id' => $this->project->id, 'member_id' => null, 'user_id' => null,
            'qr_token' => str_repeat('a', 64),
        ]);
        $response = $this->getJson('/api/queue/'.$entry->id.'?project_key=security-review');

        $this->assertContains($response->status(), [401, 403, 404], $response->getContent());
    }

    public function test_foreign_project_queue_is_not_visible(): void
    {
        $other = Project::factory()->create();
        $entry = QueueEntry::factory()->create(['project_id' => $other->id, 'member_id' => null]);

        $this->getJson('/api/queue/'.$entry->id.'?project_key=security-review')->assertNotFound();
    }

    public function test_anonymous_registration_cannot_reassign_an_existing_device(): void
    {
        $owner = User::factory()->create(['project_id' => $this->project->id]);
        $device = UserDevice::create([
            'project_id' => $this->project->id, 'user_id' => $owner->id,
            'device_identifier' => 'review-existing-device', 'platform' => 'ios',
            'push_token' => 'review-owner-push-token',
        ]);

        $this->postJson('/api/devices/register?project_key=security-review', [
            'device_identifier' => $device->device_identifier, 'platform' => 'ios',
        ]);

        $this->assertSame($owner->id, $device->fresh()->user_id);
    }

    public function test_duplicate_product_lines_cannot_oversell_stock(): void
    {
        $product = Product::factory()->create([
            'project_id' => $this->project->id, 'stock' => 1, 'is_available' => true,
        ]);
        $response = $this->postJson('/api/orders?project_key=security-review', [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 1],
                ['product_id' => $product->id, 'quantity' => 1],
            ],
            'payment_method' => 'stripe', 'order_type' => 'mobile', 'points_used' => 0,
        ]);

        $this->assertContains($response->status(), [400, 409, 422], $response->getContent());
        $this->assertSame(1, (int) $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_paid_order_cannot_create_another_payment_intent(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);
        $order = Order::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'total_amount' => 1200, 'status' => 'completed', 'payment_method' => 'stripe',
            'points_used' => 0, 'points_earned' => 0, 'order_type' => 'mobile',
        ]);
        $gateway = new FakePaymentGateway;
        $useCase = new PaymentCreateIntentUseCase(new OrderPaymentSessionService($gateway));

        $this->expectException(\InvalidArgumentException::class);
        try {
            $useCase->handle($order->id, $this->project->id, $user->id);
        } finally {
            $this->assertSame([], $gateway->created);
        }
    }

    public function test_device_identifier_alone_cannot_restore_owner_identity(): void
    {
        $owner = User::factory()->create(['project_id' => $this->project->id]);
        $device = UserDevice::create([
            'project_id' => $this->project->id, 'user_id' => $owner->id,
            'device_identifier' => 'private-device', 'platform' => 'ios',
        ]);
        $device->forceFill(['auth_token' => 'secret-device-token'])->save();
        AdminContext::setCurrentProject($this->project);
        $request = Request::create('/');
        $request->headers->set('X-Device-Identifier', 'private-device');
        $users = app(UserService::class);
        $this->assertNull($users->findUser($request));
        $request->headers->set('X-Device-Token', 'wrong-token');
        $this->assertNull($users->findUser($request));
        $request->headers->set('X-Device-Token', 'secret-device-token');
        $this->assertSame($owner->id, $users->findUser($request)?->id);
        $this->assertArrayNotHasKey('auth_token', $device->toArray());
        $this->assertNotSame('secret-device-token', $device->getRawOriginal('auth_token'));
    }

    public function test_native_registration_returns_a_credential_and_reuses_the_owner_on_retry(): void
    {
        $payload = ['device_identifier' => 'new-native-device', 'platform' => 'ios'];
        $first = $this->postJson('/api/devices/register?project_key=security-review', $payload)->assertOk();
        $token = $first->json('device_token');
        $this->assertSame(64, strlen($token));
        $this->flushSession();
        $this->withHeaders(['X-Device-Identifier' => 'new-native-device', 'X-Device-Token' => $token])
            ->postJson('/api/devices/register?project_key=security-review', $payload)
            ->assertOk()->assertJsonPath('device_id', $first->json('device_id'));
        $this->assertDatabaseCount('user_devices', 1);
    }

    public function test_queue_id_query_cannot_claim_another_customers_ticket(): void
    {
        $entry = QueueEntry::factory()->create([
            'project_id' => $this->project->id, 'member_id' => null, 'user_id' => null,
            'qr_token' => 'private-queue-token',
        ]);
        $response = $this->getJson('/api/queue/me?project_key=security-review&queue_id='.$entry->id);
        $response->assertOk()->assertJsonPath('queue_entry', null);
        $this->assertNull($entry->fresh()->user_id);
    }

    public function test_kitchen_requires_the_correct_store_token(): void
    {
        ShopSettingStore::setKitchenAccessToken('store-secret', $this->project->id);
        $url = '/api/kitchen/security-review/orders';
        $this->getJson($url)->assertUnauthorized();
        $this->withHeader('X-Store-Token', 'wrong')->getJson($url)->assertUnauthorized();
        $this->withHeader('X-Store-Token', 'store-secret')->getJson($url)->assertOk();
    }

    public function test_payment_retries_reuse_the_same_session_and_reject_a_second_channel(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);
        $order = Order::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'total_amount' => 1200, 'status' => 'pending', 'payment_method' => 'stripe',
            'points_used' => 0, 'points_earned' => 0, 'order_type' => 'mobile',
        ]);
        $gateway = \Mockery::mock(PaymentGateway::class);
        $gateway->shouldReceive('createPaymentIntent')->once()->with(1200, $order->id)
            ->andReturn(['id' => 'pi_once', 'client_secret' => 'secret_once']);
        $sessions = new OrderPaymentSessionService($gateway);
        $first = $sessions->create($order->id, $this->project->id, $user->id, 'intent');
        $this->assertSame($first, $sessions->create($order->id, $this->project->id, $user->id, 'intent'));
        $this->assertArrayNotHasKey('payment_session', $order->fresh()->toArray());
        $this->assertStringNotContainsString('secret_once', $order->fresh()->getRawOriginal('payment_session'));
        $this->expectException(\InvalidArgumentException::class);
        $sessions->create($order->id, $this->project->id, $user->id, 'checkout');
    }

    public function test_payment_gateway_runs_after_the_order_reservation_transaction(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);
        $order = Order::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'total_amount' => 1200, 'status' => 'pending',
            'payment_method' => 'stripe', 'order_type' => 'mobile',
            'points_used' => 0, 'points_earned' => 0,
        ]);
        $transactionLevel = DB::transactionLevel();
        $gateway = \Mockery::mock(PaymentGateway::class);
        $gateway->shouldReceive('createPaymentIntent')->once()->andReturnUsing(function () use ($order, $transactionLevel) {
            $this->assertSame($transactionLevel, DB::transactionLevel());
            $this->assertNotNull($order->fresh()->payment_session['lease'] ?? null);

            return ['id' => 'pi_reserved', 'client_secret' => 'secret_reserved'];
        });

        $sessions = new OrderPaymentSessionService($gateway);
        $this->assertSame('pi_reserved', $sessions->create($order->id, $this->project->id, $user->id, 'intent')['id']);
        $this->assertSame('pi_reserved', $sessions->create($order->id, $this->project->id, $user->id, 'intent')['id']);
    }

    public function test_payment_reservation_blocks_concurrent_creation_and_can_be_retried_after_failure(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);
        $order = Order::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'total_amount' => 1200, 'status' => 'pending',
            'payment_method' => 'stripe', 'order_type' => 'mobile',
            'points_used' => 0, 'points_earned' => 0,
        ]);
        $gateway = \Mockery::mock(PaymentGateway::class);
        $gateway->shouldReceive('createPaymentIntent')->once()->andReturnUsing(function () use ($order, $user) {
            try {
                (new OrderPaymentSessionService(\Mockery::mock(PaymentGateway::class)))
                    ->create($order->id, $this->project->id, $user->id, 'intent');
                $this->fail('A second gateway call should be blocked');
            } catch (PaymentSessionInProgressException) {
                // The first reservation is still active.
            }

            throw new \RuntimeException('gateway unavailable');
        });
        $sessions = new OrderPaymentSessionService($gateway);
        try {
            $sessions->create($order->id, $this->project->id, $user->id, 'intent');
            $this->fail('The gateway error should propagate');
        } catch (\RuntimeException $e) {
            $this->assertSame('gateway unavailable', $e->getMessage());
        }
        $this->assertNull($order->fresh()->payment_session);
    }

    public function test_expired_payment_reservation_can_be_recovered(): void
    {
        $user = User::factory()->create(['project_id' => $this->project->id]);
        $order = Order::create([
            'project_id' => $this->project->id, 'user_id' => $user->id,
            'total_amount' => 1200, 'status' => 'pending',
            'payment_method' => 'stripe', 'order_type' => 'mobile',
            'points_used' => 0, 'points_earned' => 0,
            'payment_session' => [
                'channel' => 'intent', 'lease' => 'expired',
                'expires_at' => now()->subMinute()->toIso8601String(),
            ],
        ]);
        $gateway = \Mockery::mock(PaymentGateway::class);
        $gateway->shouldReceive('createPaymentIntent')->once()
            ->andReturn(['id' => 'pi_recovered', 'client_secret' => 'secret_recovered']);

        $result = (new OrderPaymentSessionService($gateway))
            ->create($order->id, $this->project->id, $user->id, 'intent');

        $this->assertSame('pi_recovered', $result['id']);
        $this->assertSame($result, $order->fresh()->payment_session['result']);
    }
}
