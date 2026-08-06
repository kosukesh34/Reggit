<?php

namespace Tests\Unit\Services\Smaregi;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\SmaregiTransactionHeadDivision;
use App\Jobs\SyncOrderTransactionToSmaregiJob;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiOrderTransactionOutboundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class SmaregiOrderTransactionOutboundServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_未連携商品がある注文は同期せず_ap_iを呼ばないこと(): void
    {
        Queue::fake();

        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', 'contract-1', 'string', (int) $project->id);
        ShopSetting::setValue('smaregi_store_id', '1', 'string', (int) $project->id);
        ShopSetting::setValue('smaregi_terminal_id', '1', 'string', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
        ]);
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'smaregi_product_id' => null,
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1000,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::CASH,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => OrderType::MOBILE,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1000,
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $smaregiApi->shouldNotReceive('createTransaction');
        $smaregiApi->shouldNotReceive('createLayaway');

        $service = new SmaregiOrderTransactionOutboundService(
            app(OrderRepository::class),
            app(MemberRepository::class),
            $smaregiApi,
        );

        $this->assertFalse($service->syncOrderTransaction(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));
        $this->assertNull($order->fresh()->smaregi_transaction_synced_at);
    }

    public function test_現金注文は取置き登録し通常販売にはしないこと(): void
    {
        Queue::fake();

        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', 'contract-1', 'string', (int) $project->id);
        ShopSetting::setValue('smaregi_store_id', '10', 'string', (int) $project->id);
        ShopSetting::setValue('smaregi_terminal_id', '20', 'string', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
        ]);
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'smaregi_product_id' => '8000205',
            'name' => 'ケーキ',
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1500,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::CASH,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => OrderType::IN_STORE,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1500,
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $smaregiApi->shouldReceive('createLayaway')
            ->once()
            ->withArgs(function (string $contractId, array $body) use ($order): bool {
                return $contractId === 'contract-1'
                    && $body['transactionHeadDivision'] === SmaregiTransactionHeadDivision::LAYAWAY
                    && $body['terminalTranId'] === 'RG'.$order->id
                    && ! isset($body['depositCash']);
            })
            ->andReturn(['transactionHeadId' => '8001']);
        $smaregiApi->shouldNotReceive('createTransaction');

        $service = new SmaregiOrderTransactionOutboundService(
            app(OrderRepository::class),
            app(MemberRepository::class),
            $smaregiApi,
        );

        $this->assertTrue($service->syncOrderTransaction(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));

        $fresh = $order->fresh();
        $this->assertSame('8001', $fresh->smaregi_layaway_head_id);
        $this->assertNull($fresh->smaregi_transaction_synced_at);
        $this->assertNull($fresh->smaregi_transaction_head_id);
    }

    public function test_現金取置きの完了で通常販売として引取確定すること(): void
    {
        Queue::fake();

        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', 'contract-1', 'string', (int) $project->id);
        ShopSetting::setValue('smaregi_store_id', '10', 'string', (int) $project->id);
        ShopSetting::setValue('smaregi_terminal_id', '20', 'string', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
        ]);
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'smaregi_product_id' => '8000205',
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1500,
            'status' => OrderStatus::COMPLETED,
            'payment_method' => PaymentMethod::CASH,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => OrderType::IN_STORE,
            'smaregi_layaway_head_id' => '8001',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1500,
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $smaregiApi->shouldNotReceive('createLayaway');
        $smaregiApi->shouldReceive('createTransaction')
            ->once()
            ->withArgs(function (string $contractId, array $body) use ($order): bool {
                return $contractId === 'contract-1'
                    && $body['transactionHeadDivision'] === SmaregiTransactionHeadDivision::NORMAL
                    && $body['layawayServerTransactionHeadId'] === '8001'
                    && $body['terminalTranId'] === 'RGP'.$order->id
                    && $body['depositCash'] === '1500';
            })
            ->andReturn(['transactionHeadId' => '9001']);

        $service = new SmaregiOrderTransactionOutboundService(
            app(OrderRepository::class),
            app(MemberRepository::class),
            $smaregiApi,
        );

        $this->assertTrue($service->finalizeCashLayaway(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));

        $fresh = $order->fresh();
        $this->assertSame('9001', $fresh->smaregi_transaction_head_id);
        $this->assertNotNull($fresh->smaregi_transaction_synced_at);
        $this->assertSame('8001', $fresh->smaregi_layaway_head_id);
    }

    public function test_stripe注文は通常販売として登録し冪等になること(): void
    {
        Queue::fake();

        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', 'contract-1', 'string', (int) $project->id);
        ShopSetting::setValue('smaregi_store_id', '10', 'string', (int) $project->id);
        ShopSetting::setValue('smaregi_terminal_id', '20', 'string', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
        ]);
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'smaregi_product_id' => '8000205',
            'name' => 'ケーキ',
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1500,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => OrderType::MOBILE,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1500,
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $smaregiApi->shouldReceive('createTransaction')
            ->once()
            ->withArgs(function (string $contractId, array $body) use ($order): bool {
                return $contractId === 'contract-1'
                    && $body['transactionHeadDivision'] === SmaregiTransactionHeadDivision::NORMAL
                    && $body['terminalTranId'] === 'RG'.$order->id
                    && ! isset($body['layawayServerTransactionHeadId'])
                    && ! isset($body['newPoint']);
            })
            ->andReturn(['transactionHeadId' => '9001']);
        $smaregiApi->shouldNotReceive('createLayaway');

        $service = new SmaregiOrderTransactionOutboundService(
            app(OrderRepository::class),
            app(MemberRepository::class),
            $smaregiApi,
        );

        $this->assertTrue($service->syncOrderTransaction(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));

        $fresh = $order->fresh();
        $this->assertSame('9001', $fresh->smaregi_transaction_head_id);
        $this->assertNotNull($fresh->smaregi_transaction_synced_at);

        $this->assertTrue($service->syncOrderTransaction(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));
    }

    public function test_失敗時はリトライジョブを積むこと(): void
    {
        Queue::fake();

        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', 'contract-1', 'string', (int) $project->id);
        ShopSetting::setValue('smaregi_store_id', '10', 'string', (int) $project->id);
        ShopSetting::setValue('smaregi_terminal_id', '20', 'string', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
        ]);
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'smaregi_product_id' => '8000205',
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 500,
            'status' => OrderStatus::CONFIRMED,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => OrderType::MOBILE,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 500,
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $smaregiApi->shouldReceive('createTransaction')->once()->andReturn(null);

        $service = new SmaregiOrderTransactionOutboundService(
            app(OrderRepository::class),
            app(MemberRepository::class),
            $smaregiApi,
        );

        $this->assertFalse($service->syncOrderTransactionWithRetry(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));
        Queue::assertPushed(SyncOrderTransactionToSmaregiJob::class);
    }

    public function test_stripe未決済は取引登録しないこと(): void
    {
        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', 'contract-1', 'string', (int) $project->id);
        ShopSetting::setValue('smaregi_store_id', '10', 'string', (int) $project->id);
        ShopSetting::setValue('smaregi_terminal_id', '20', 'string', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
        ]);
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'smaregi_product_id' => '8000205',
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 1500,
            'status' => OrderStatus::PENDING,
            'payment_method' => PaymentMethod::STRIPE,
            'points_used' => 0,
            'points_earned' => 0,
            'order_type' => OrderType::MOBILE,
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 1500,
        ]);

        $smaregiApi = Mockery::mock(SmaregiApiService::class);
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $smaregiApi->shouldNotReceive('createTransaction');
        $smaregiApi->shouldNotReceive('createLayaway');

        $service = new SmaregiOrderTransactionOutboundService(
            app(OrderRepository::class),
            app(MemberRepository::class),
            $smaregiApi,
        );

        $this->assertTrue($service->syncOrderTransaction(
            (int) $order->id,
            (int) $project->id,
            (int) $user->id,
        ));
        $this->assertNull($order->fresh()->smaregi_transaction_head_id);
    }
}
