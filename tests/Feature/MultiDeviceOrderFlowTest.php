<?php

namespace Tests\Feature;

use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Models\AdminUser;
use App\Models\Member;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\StoreAppCredential;
use App\Models\User;
use App\Services\OrderNumberAssigner;
use App\Services\PaidOrderRewardApplier;
use App\Services\PostCommitDomainEventDispatcher;
use App\UseCases\Admin\Order\AdminOrderUpdateStatusUseCase;
use App\UseCases\Payment\PaymentWebhookUseCase;
use App\Util\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FakePaymentGateway;
use Tests\TestCase;

class MultiDeviceOrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private function authenticateKitchen(Project $project): void
    {
        $admin = AdminUser::factory()->create(['project_id' => $project->id, 'role' => AdminUser::ROLE_ADMIN]);
        $token = $admin->createToken('test-kitchen', ['app:kitchen', 'project:'.$project->id]);
        StoreAppCredential::query()->create([
            'project_id' => $project->id, 'admin_user_id' => $admin->id, 'app' => 'kitchen',
            'personal_access_token_id' => $token->accessToken->id,
            'device_key_hash' => hash('sha256', 'test-device-key'), 'claimed_at' => now(),
        ]);
        $this->withToken($token->plainTextToken)->withHeader('X-Device-Key', 'test-device-key');
    }

    public function test_cash_order_is_hidden_from_kitchen_until_staff_confirms_it(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'is_active' => true]
        );
        $customerId = 'U00000000000000000000000000000991';
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => $customerId]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $product = Product::factory()->create(['project_id' => $project->id, 'name' => '大盛りラテ', 'price' => 650, 'stock' => 10]);
        $this->authenticateKitchen($project);

        $created = $this->withHeader('X-Line-User-Id', $customerId)
            ->postJson('/api/orders?project_key=default', [
                'items' => [['product_id' => $product->id, 'quantity' => 2]],
                'payment_method' => PaymentMethod::CASH,
                'points_used' => 0,
                'order_type' => OrderType::MOBILE,
            ])->assertCreated();

        $orderId = (int) $created->json('order.id');
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'project_id' => $project->id, 'user_id' => $user->id, 'status' => 'pending']);
        $this->assertDatabaseHas('order_items', ['order_id' => $orderId, 'product_id' => $product->id, 'quantity' => 2]);

        $this->getJson('/api/kitchen/default/orders')
            ->assertOk()
            ->assertJsonCount(0, 'orders');

        app(AdminOrderUpdateStatusUseCase::class)->handle($orderId, 'confirmed');

        $this->getJson('/api/kitchen/default/orders')
            ->assertOk()
            ->assertJsonPath('orders.0.id', $orderId)
            ->assertJsonPath('orders.0.items.0.name', '大盛りラテ')
            ->assertJsonPath('orders.0.items.0.quantity', 2);

        $this->assertSame('confirmed', Order::findOrFail($orderId)->status);
    }

    public function test_card_order_appears_on_kitchen_ipad_only_after_payment_webhook(): void
    {
        Queue::fake();
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'is_active' => true]
        );
        $customerId = 'U00000000000000000000000000000992';
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => $customerId]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $product = Product::factory()->create(['project_id' => $project->id, 'stock' => 10]);
        $this->authenticateKitchen($project);

        $created = $this->withHeader('X-Line-User-Id', $customerId)
            ->postJson('/api/orders?project_key=default', [
                'items' => [['product_id' => $product->id, 'quantity' => 1]],
                'payment_method' => PaymentMethod::STRIPE,
                'points_used' => 0,
                'order_type' => OrderType::MOBILE,
            ])->assertCreated();

        $orderId = (int) $created->json('order.id');
        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'pending', 'order_number' => null]);
        $this->getJson('/api/kitchen/default/orders')
            ->assertOk()
            ->assertJsonCount(0, 'orders');

        $gateway = new FakePaymentGateway;
        $gateway->event = [
            'type' => 'payment_intent.succeeded', 'currency' => 'jpy',
            'metadata' => ['order_id' => $orderId],
            'amount' => (int) $created->json('order.total_amount'),
        ];
        $webhook = new PaymentWebhookUseCase(
            $gateway,
            app(OrderRepository::class),
            app(PostCommitDomainEventDispatcher::class),
            app(OrderNumberAssigner::class),
            app(PaidOrderRewardApplier::class),
            app(StoreContext::class),
        );
        $this->assertTrue($webhook->handle('{}', 'sig', 'secret'));

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'confirmed']);
        $this->getJson('/api/kitchen/default/orders')
            ->assertOk()
            ->assertJsonPath('orders.0.id', $orderId);
    }
}
