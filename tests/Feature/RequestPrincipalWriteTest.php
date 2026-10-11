<?php

namespace Tests\Feature;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Order;
use App\Models\Product;
use App\Models\Project;
use App\Models\User;
use App\Services\OrderService;
use App\UseCases\Order\OrderCancelUnpaidUseCase;
use App\UseCases\Queue\Data\QueueStoreInput;
use App\UseCases\Queue\QueueStoreUseCase;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class RequestPrincipalWriteTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['order_create', 'revoked'])]
    #[TestWith(['order_cancel', 'revoked'])]
    #[TestWith(['queue', 'revoked'])]
    #[TestWith(['order_create', 'deleted'])]
    #[TestWith(['order_cancel', 'deleted'])]
    #[TestWith(['queue', 'deleted'])]
    public function test_stale_request_cannot_mutate_orders_or_create_queue_entries(string $action, string $change): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setValue('queue_require_membership', false, 'boolean', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $product = Product::factory()->create(['project_id' => $project->id, 'stock' => 10]);
        $order = Order::create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'total_amount' => 1000, 'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE, 'order_type' => OrderType::MOBILE,
            'points_used' => 0, 'points_earned' => 0,
        ]);
        $user->load('member');
        if ($change === 'revoked') {
            $user->fresh()->increment('authentication_version');
        } else {
            $user->fresh()->delete();
        }
        if ($action === 'queue') {
            $result = app(QueueStoreUseCase::class)->handle(new QueueStoreInput($user, 1, null, null, null, null));
            $this->assertTrue($result->requiresMembership);
            $this->assertNull($result->entry);
        } else {
            $rejected = false;
            try {
                if ($action === 'order_create') {
                    app(OrderService::class)->createOrder([
                        'items' => [['product_id' => $product->id, 'quantity' => 1]],
                        'payment_method' => PaymentMethod::CASH, 'order_type' => OrderType::MOBILE,
                    ], $user);
                } else {
                    app(OrderCancelUnpaidUseCase::class)->handle($user, (int) $order->id);
                }
            } catch (OrderOperationException $exception) {
                $this->assertSame('ログインし直してください', $exception->getMessage());
                $rejected = true;
            }
            $this->assertTrue($rejected);
        }
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(OrderStatus::PENDING, $order->fresh()->status);
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertDatabaseCount('queue_entries', 0);
    }
}
