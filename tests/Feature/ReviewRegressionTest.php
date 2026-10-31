<?php

namespace Tests\Feature;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Enums\OrderStatus;
use App\Http\Middleware\ValidateCsrfToken;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\ApiToken;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Admin\OrderAdminService;
use App\Services\Member\MemberPointLedger;
use App\Services\MemberPointsExpiryCalculator;
use App\Services\OrderService;
use App\UseCases\Payment\PaymentGateway;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class ReviewRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_native_device_bootstrap_obtains_csrf_and_registers_without_bypassing_protection(): void
    {
        $project = Project::factory()->create();
        $this->withMiddleware(ValidateCsrfToken::class);
        $this->app->bind(ValidateCsrfToken::class, fn ($app) => new class($app, $app['encrypter']) extends ValidateCsrfToken
        {
            protected function runningUnitTests()
            {
                return false;
            }
        });
        $this->withHeader('X-Project-Key', $project->project_key);
        $payload = ['device_identifier' => 'new-native-device', 'platform' => 'ios'];
        $this->postJson('/api/devices/register', $payload)->assertStatus(419);
        $token = $this->getJson('/api/devices/session')->assertOk()->json('csrf_token');
        $this->assertNotEmpty($token);
        $this->withSession(['_token' => $token])->withHeader('X-CSRF-TOKEN', $token)
            ->postJson('/api/devices/register', $payload)->assertOk()->assertJsonStructure(['device_token']);
        $this->assertDatabaseCount('user_devices', 1);
    }

    public function test_legacy_token_api_uses_the_tokens_store_instead_of_the_default_store(): void
    {
        $default = Project::where('project_key', 'default')->firstOrFail();
        $other = Project::factory()->create(['project_key' => 'other-brand']);
        config(['app.default_project_key' => $default->project_key]);
        $user = User::factory()->create(['project_id' => $other->id, 'line_user_id' => 'U'.str_repeat('a', 32)]);
        $order = Order::create(['project_id' => $other->id, 'user_id' => $user->id, 'total_amount' => 1000, 'status' => 'pending', 'payment_method' => 'stripe', 'order_type' => 'mobile']);
        $token = ApiToken::issue($other, 'regression')['plain'];
        $this->withToken($token)->withHeader('X-Line-User-Id', $user->line_user_id)
            ->getJson('/api/v1/orders')->assertOk()->assertJsonPath('data.orders.0.id', $order->id);
    }

    public function test_v1_queue_cannot_bypass_membership_with_a_client_header(): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('queue_require_membership', true, 'boolean', $project->id);
        $token = ApiToken::issue($project, 'regression')['plain'];
        $this->withToken($token)->withHeader('X-Queue-Client', 'waiting-system')
            ->postJson('/api/v1/'.$project->project_key.'/queue', ['party_size' => 2])->assertForbidden();
        $this->assertDatabaseCount('queue_entries', 0);
    }

    public function test_native_device_coupon_routes_list_and_claim_once(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $device = UserDevice::create(['project_id' => $project->id, 'user_id' => $user->id, 'platform' => 'ios', 'device_identifier' => 'coupon-device']);
        $device->auth_token = 'fixture-device-token';
        $device->save();
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'is_active' => true, 'device_once_enabled' => true]);
        $this->withHeaders(['X-Project-Key' => $project->project_key, 'X-Device-Identifier' => $device->device_identifier, 'X-Device-Token' => $device->auth_token]);
        $this->getJson('/api/device-coupons')->assertOk()->assertJsonPath('data.coupons.0.id', $coupon->id);
        $this->postJson('/api/device-coupons/'.$coupon->id.'/claim')->assertOk();
        $this->postJson('/api/device-coupons/'.$coupon->id.'/claim')->assertUnprocessable();
        $this->assertDatabaseCount('member_coupons', 1);
    }

    public function test_admin_cancellation_stops_payment_and_returns_stock_and_points_once(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'points' => 80]);
        $product = Product::factory()->create(['project_id' => $project->id, 'stock' => 8]);
        $order = Order::create(['project_id' => $project->id, 'user_id' => $user->id, 'order_type' => 'mobile', 'payment_method' => 'stripe', 'status' => 'pending', 'points_used' => 20, 'points_earned' => 0, 'total_amount' => 1000]);
        OrderItem::create(['project_id' => $project->id, 'order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2, 'price' => 500]);
        PointTransaction::create(['project_id' => $project->id, 'member_id' => $member->id, 'type' => 'earned', 'points' => 100]);
        PointTransaction::create(['project_id' => $project->id, 'member_id' => $member->id, 'type' => 'used', 'points' => -20, 'order_id' => $order->id]);
        $order->payment_session = ['channel' => 'card', 'result' => ['id' => 'pi_fixture']];
        $order->save();
        $gateway = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $gateway);
        app(OrderAdminService::class)->updateStatus($order->id, OrderStatus::CANCELLED);
        app(OrderAdminService::class)->updateStatus($order->id, OrderStatus::CANCELLED);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(100, app(MemberPointLedger::class)->displayPoints($member->fresh()));
        $this->assertCount(1, $gateway->cancelledSessions);
        $this->expectException(OrderOperationException::class);
        app(OrderAdminService::class)->updateStatus($order->id, OrderStatus::CONFIRMED);
    }

    public function test_expired_points_are_not_resurrected_from_the_cached_column(): void
    {
        $project = Project::factory()->create();
        $member = Member::factory()->create(['project_id' => $project->id, 'points' => 100]);
        PointTransaction::create(['project_id' => $project->id, 'member_id' => $member->id, 'type' => 'earned', 'points' => 100, 'expires_at' => now()->subDay()]);
        $this->assertSame(0, app(MemberPointLedger::class)->displayPoints($member));
        app(MemberPointLedger::class)->sync($member);
        $this->assertSame(0, $member->fresh()->points);
        $this->assertDatabaseCount('point_transactions', 1);
        $this->assertNull(app(MemberPointsExpiryCalculator::class)->getExpiryInfo($member));
    }

    public function test_an_order_cannot_spend_expired_points_and_rolls_back_stock(): void
    {
        $project = Project::where('project_key', 'default')->firstOrFail();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'points' => 100]);
        PointTransaction::create(['project_id' => $project->id, 'member_id' => $member->id, 'type' => 'earned', 'points' => 100, 'expires_at' => now()->subDay()]);
        $product = Product::factory()->create(['project_id' => $project->id, 'stock' => 5, 'is_available' => true]);
        try {
            app(OrderService::class)->createOrder([
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'payment_method' => 'stripe', 'order_type' => 'mobile', 'points_used' => 50,
            ], $user);
            $this->fail('Expired points must not pay for an order');
        } catch (OrderOperationException $e) {
            $this->assertStringContainsString('ポイント', $e->getMessage());
        }
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseMissing('point_transactions', ['member_id' => $member->id, 'type' => 'used']);
    }

    public function test_cancelled_order_cannot_be_completed(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create(['project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 1000, 'status' => 'cancelled', 'payment_method' => 'stripe', 'order_type' => 'mobile']);
        try {
            app(OrderAdminService::class)->complete($order->id);
            $this->fail('A cancelled order cannot be completed');
        } catch (OrderOperationException) {
            $this->assertSame('cancelled', $order->fresh()->status);
        }
    }
}
