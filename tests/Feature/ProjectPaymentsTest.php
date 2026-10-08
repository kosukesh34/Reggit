<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Member;
use App\Models\MemberVisit;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\Settings\ProjectPaymentSetting;
use App\Models\Store;
use App\Models\User;
use App\UseCases\Payment\PaymentGateway;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class ProjectPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private const USER_ID = 'U00000000000000000000000000000123';

    private function setting(Project $project, array $methods = ['card', 'paypay', 'apple_pay', 'google_pay', 'cash']): ProjectPaymentSetting
    {
        return ProjectPaymentSetting::create([
            'project_id' => $project->id, 'account_id' => 'acct_fixture',
            'publishable_key' => 'pk_test_fixture', 'secret_key' => 'sk_test_fixture',
            'webhook_secret' => 'whsec_fixture', 'methods' => $methods,
        ]);
    }

    private function order(Project $project, ProjectPaymentSetting $setting, string $brand = 'paypay'): Order
    {
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => self::USER_ID]);
        $order = Order::create([
            'project_id' => $project->id, 'user_id' => $user->id, 'order_type' => 'mobile',
            'payment_method' => 'stripe', 'status' => 'pending', 'total_amount' => 980,
            'points_used' => 0, 'points_earned' => 0,
        ]);
        $order->forceFill(['payment_setting_id' => $setting->id, 'payment_brand' => $brand])->save();

        return $order;
    }

    public function test_methods_are_project_specific_and_secrets_are_not_exposed(): void
    {
        $one = Project::factory()->create();
        $two = Project::factory()->create();
        $setting = $this->setting($one, ['paypay', 'cash']);
        $this->setting($two, ['card', 'apple_pay']);
        $this->getJson('/api/payment/methods?project_key='.$one->project_key)->assertOk()
            ->assertExactJson(['methods' => ['paypay', 'cash'], 'test_mode' => true]);
        $this->getJson('/api/payment/methods?project_key='.$two->project_key)->assertOk()
            ->assertExactJson(['methods' => ['card', 'apple_pay'], 'test_mode' => true]);
        $this->assertNotSame('sk_test_fixture', DB::table('project_payment_settings')->where('id', $setting->id)->value('secret_key'));
        $this->assertArrayNotHasKey('secret_key', $setting->toArray());
        $this->assertArrayNotHasKey('webhook_secret', $setting->toArray());
    }

    public static function brands(): array
    {
        return [['card'], ['paypay'], ['apple_pay'], ['google_pay'], ['cash']];
    }

    #[DataProvider('brands')]
    public function test_order_records_selected_brand_and_credential_version(string $brand): void
    {
        $project = Project::factory()->create();
        $setting = $this->setting($project);
        User::factory()->create(['project_id' => $project->id, 'line_user_id' => self::USER_ID]);
        $product = Product::factory()->create(['project_id' => $project->id, 'price' => 980, 'stock' => 10]);
        $payload = ['items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => $brand === 'cash' ? 'cash' : 'stripe', 'order_type' => 'mobile'];
        if ($brand !== 'cash') {
            $payload['payment_brand'] = $brand;
        }
        $response = $this->withHeaders(['X-Line-User-Id' => self::USER_ID])
            ->postJson('/api/orders?project_key='.$project->project_key, $payload)->assertCreated();
        $order = Order::withoutGlobalScopes()->findOrFail($response->json('order.id'));
        $this->assertSame($brand, $order->payment_brand);
        $this->assertEquals($brand === 'cash' ? null : $setting->id, $order->payment_setting_id);
        $this->assertArrayNotHasKey('payment_setting_id', $response->json('order'));
        if ($brand !== 'cash') {
            $this->assertSame(OrderStatus::PENDING, $order->status);
            $this->assertNull($order->order_number);
        }
    }

    public function test_disabled_brand_and_unconfigured_project_are_rejected(): void
    {
        $project = Project::factory()->create();
        $setting = $this->setting($project, ['cash']);
        User::factory()->create(['project_id' => $project->id, 'line_user_id' => self::USER_ID]);
        $product = Product::factory()->create(['project_id' => $project->id]);
        $payload = ['items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payment_method' => 'stripe', 'payment_brand' => 'paypay', 'order_type' => 'mobile'];
        $this->withHeaders(['X-Line-User-Id' => self::USER_ID])->postJson('/api/orders?project_key='.$project->project_key, $payload)->assertStatus(400);
        $setting->delete();
        $payload['payment_brand'] = 'card';
        $this->postJson('/api/orders?project_key='.$project->project_key, $payload)->assertStatus(400);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_paypay_checkout_reuses_session_and_keeps_original_settings_after_update(): void
    {
        $project = Project::factory()->create();
        $setting = $this->setting($project);
        $order = $this->order($project, $setting);
        $this->setting($project, ['cash']);
        $fake = new FakePaymentGateway;
        $this->app->instance(PaymentGateway::class, $fake);
        $url = '/api/payment/checkout?project_key='.$project->project_key;
        $this->withHeaders(['X-Line-User-Id' => self::USER_ID])->postJson($url, ['order_id' => $order->id, 'surface' => 'web'])
            ->assertOk()->assertJsonPath('session_id', 'cs_test_123');
        $this->assertStringContainsString('surface=web', $fake->checkoutCreated['successUrl']);
        $this->assertStringContainsString('project_key='.$project->project_key, $fake->checkoutCreated['successUrl']);
        $fake->checkoutCreated = [];
        $this->postJson($url, ['order_id' => $order->id, 'surface' => 'web'])->assertOk();
        $this->assertSame([], $fake->checkoutCreated);
        $this->assertEquals($setting->id, $order->fresh()->payment_setting_id);
    }

    public function test_payment_from_another_project_cannot_confirm_the_order(): void
    {
        $project = Project::factory()->create();
        $setting = $this->setting($project);
        $order = $this->order($project, $setting);
        $order->payment_session = ['channel' => 'checkout', 'result' => ['id' => 'cs_test_123']];
        $order->save();
        $fake = new FakePaymentGateway;
        $fake->intent = ['status' => 'succeeded', 'amount' => 980, 'currency' => 'jpy', 'metadata' => [
            'order_id' => $order->id, 'project_id' => $project->id + 1, 'payment_setting_id' => $setting->id,
        ]];
        $this->app->instance(PaymentGateway::class, $fake);
        $this->withHeaders(['X-Line-User-Id' => self::USER_ID])->postJson('/api/payment/checkout/confirm?project_key='.$project->project_key, ['order_id' => $order->id])->assertStatus(400);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_project_checkout_confirms_paypay_and_replay_keeps_the_same_pickup_number(): void
    {
        Queue::fake();
        $project = Project::factory()->create();
        $setting = $this->setting($project);
        $order = $this->order($project, $setting);
        $order->payment_session = ['channel' => 'checkout_web', 'result' => ['id' => 'cs_test_123']];
        $order->save();
        $fake = new FakePaymentGateway;
        $fake->intent = ['status' => 'succeeded', 'amount' => 980, 'currency' => 'jpy', 'paid_payment_brand' => 'paypay', 'metadata' => [
            'order_id' => $order->id, 'project_id' => $project->id, 'payment_setting_id' => $setting->id,
        ]];
        $this->app->instance(PaymentGateway::class, $fake);
        $url = '/api/payment/checkout/confirm?project_key='.$project->project_key;
        $this->withHeaders(['X-Line-User-Id' => self::USER_ID])->postJson($url, ['order_id' => $order->id])
            ->assertOk()->assertJsonPath('order.payment_brand', 'paypay')->assertJsonPath('order.paid_payment_brand', 'paypay');
        $number = $order->fresh()->order_number;
        $this->assertNotEmpty($number);
        $this->postJson($url, ['order_id' => $order->id])->assertOk();
        $this->assertSame($number, $order->fresh()->order_number);
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_webhook_rejects_wrong_signature_and_accepts_the_projects_signature(): void
    {
        Queue::fake();
        $project = Project::factory()->create();
        $setting = $this->setting($project);
        $order = $this->order($project, $setting);
        $payload = json_encode(['id' => 'evt_fixture', 'object' => 'event', 'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_fixture', 'object' => 'payment_intent', 'status' => 'succeeded', 'amount' => 980, 'currency' => 'jpy',
                'metadata' => ['order_id' => $order->id, 'project_id' => $project->id, 'payment_setting_id' => $setting->id]]]], JSON_THROW_ON_ERROR);
        $url = route('payment.project.webhook', ['project_key' => $project->project_key, 'setting' => $setting->id]);
        $timestamp = time();
        $wrong = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_other');
        $this->call('POST', $url, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $wrong], $payload)->assertStatus(400);
        $this->assertSame('pending', $order->fresh()->status);
        $correct = 't='.$timestamp.',v1='.hash_hmac('sha256', $timestamp.'.'.$payload, $setting->webhook_secret);
        $this->call('POST', $url, [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $correct], $payload)->assertOk();
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_project_webhook_cannot_confirm_an_order_in_another_project(): void
    {
        Queue::fake();
        $one = Project::factory()->create();
        $two = Project::factory()->create();
        $settingOne = $this->setting($one);
        $settingTwo = $this->setting($two);
        $order = $this->order($two, $settingTwo);
        $fake = new FakePaymentGateway;
        $fake->event = ['type' => 'payment_intent.succeeded', 'amount' => 980, 'currency' => 'jpy', 'metadata' => [
            'order_id' => $order->id, 'project_id' => $one->id, 'payment_setting_id' => $settingOne->id,
        ]];
        $this->app->instance(PaymentGateway::class, $fake);
        $this->postJson(route('payment.project.webhook', ['project_key' => $one->project_key, 'setting' => $settingOne->id]))->assertOk();
        $this->assertSame('pending', Order::withoutGlobalScopes()->findOrFail($order->id)->status);
        Queue::assertNothingPushed();
    }

    public function test_admin_can_save_account_and_secrets_are_never_rendered_or_flashed(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create(['project_id' => $project->id, 'role' => AdminUser::ROLE_ADMIN]);
        $http = $this->createMock(ClientInterface::class);
        $http->method('request')->willReturn(['{"id":"acct_fixture","object":"account"}', 200, []]);
        $original = ApiRequestor::httpClient();
        try {
            ApiRequestor::setHttpClient($http);
            $this->actingAs($admin, 'admin')->post(route('admin.settings.payments.update', ['project_key' => $project->project_key]), [
                'publishable_key' => 'pk_test_fixture', 'secret_key' => 'sk_test_fixture',
                'webhook_secret' => 'whsec_fixture', 'methods' => ['card', 'paypay', 'apple_pay', 'google_pay', 'cash'],
            ])->assertRedirect();
        } finally {
            ApiRequestor::setHttpClient($original);
        }
        $setting = ProjectPaymentSetting::current($project->id);
        $this->assertNotNull($setting);
        $this->get(route('admin.settings.payments', ['project_key' => $project->project_key]))->assertOk()
            ->assertSee('acct_fixture')->assertDontSee('sk_test_fixture')->assertDontSee('whsec_fixture');
        $this->post(route('admin.settings.payments.update', ['project_key' => $project->project_key]), [
            'publishable_key' => 'invalid', 'secret_key' => 'sk_test_sensitive', 'webhook_secret' => 'whsec_sensitive',
        ])->assertSessionHasErrors();
        $this->assertNull(session('_old_input.secret_key'));
        $this->assertNull(session('_old_input.webhook_secret'));
    }

    public function test_legacy_webhook_assigns_number_and_rewards_in_the_orders_store_without_frontend_confirm(): void
    {
        Queue::fake();
        $project = Project::where('project_key', 'default')->firstOrFail();
        $store = Store::where('project_id', $project->id)->firstOrFail();
        $secondStore = Store::create(['project_id' => $project->id, 'name' => 'Second', 'store_key' => 'second', 'is_active' => true]);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $order = Order::create([
            'project_id' => $project->id, 'store_id' => $secondStore->id, 'user_id' => $user->id,
            'order_type' => 'mobile', 'payment_method' => 'stripe', 'status' => 'pending',
            'total_amount' => 1000, 'points_used' => 0, 'points_earned' => 0,
        ]);
        $gateway = new FakePaymentGateway;
        $gateway->event = ['type' => 'payment_intent.succeeded', 'metadata' => ['order_id' => $order->id], 'amount' => 1000];
        $this->app->instance(PaymentGateway::class, $gateway);
        config(['services.stripe.webhook_secret' => 'whsec_fixture']);
        ShopSettingStore::setValue('use_points', true, 'boolean', $project->id);
        ShopSettingStore::setValue('points_per_order', 10, 'integer', $project->id);
        $this->postJson('/api/payment/webhook?store_id='.$store->id)->assertOk();
        $fresh = $order->fresh();
        $this->assertSame('confirmed', $fresh->status);
        $this->assertNotEmpty($fresh->order_number);
        $this->assertGreaterThanOrEqual(10, $fresh->points_earned);
        $this->assertDatabaseHas('member_visits', ['order_id' => $order->id, 'store_id' => $secondStore->id, 'member_id' => $member->id]);
        $points = $fresh->points_earned;
        $this->postJson('/api/payment/webhook')->assertOk();
        $this->assertSame($points, $order->fresh()->points_earned);
        $this->assertSame(1, MemberVisit::withoutGlobalScopes()->where('order_id', $order->id)->count());
        $this->assertNull(AdminContext::getCurrentProjectId());
        $this->assertFalse(app(StoreContext::class)->bound);
    }
}
