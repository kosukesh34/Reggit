<?php

namespace Tests\Feature;

use App\Enums\MemberVisitSource;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Enums\PaymentMethod;
use App\Enums\PointTransactionType;
use App\Enums\SmaregiCancelDivision;
use App\Enums\SmaregiEvent;
use App\Enums\SmaregiReturnSalesDivision;
use App\Enums\SmaregiTransactionHeadDivision;
use App\Enums\SmaregiWebhookAction;
use App\Infrastructure\Persistence\Eloquent\Settings\MemberRankSettingStore;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Jobs\SmaregiSyncInboundTransactionsJob;
use App\Models\Member;
use App\Models\MemberStamp;
use App\Models\MemberStampTransaction;
use App\Models\MemberVisit;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\Project;
use App\Models\StampCard;
use App\Models\User;
use App\Services\Admin\MemberAdminQueryService;
use App\Services\Smaregi\SmaregiTransactionInboundSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SmaregiInboundTransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    private const CONTRACT_ID = 'test_contract_tx_history';

    /** @var array<string, array<string, string|int|float|bool|array|null>> */
    private array $transactionHttpFixtures = [];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->transactionHttpFixtures = [];
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
            'smaregi.contract_id' => '',
            'smaregi.webhook_secret' => '',
            'smaregi.webhook_secret_header' => '',
        ]);
    }

    public function test_webhook_pos_transactionsで取引履歴同期ジョブがディスパッチされること(): void
    {
        Bus::fake();

        $this->postJson('/api/smaregi/webhook', [
            'contractId' => self::CONTRACT_ID,
            'event' => SmaregiEvent::POS_TRANSACTIONS,
            'action' => SmaregiWebhookAction::CREATED,
            'transactionHeadIds' => ['39'],
        ], [
            'smaregi-event' => SmaregiEvent::POS_TRANSACTIONS,
            'smaregi-contract-id' => self::CONTRACT_ID,
            'Content-Type' => 'application/json',
        ])->assertStatus(200);

        Bus::assertDispatched(SmaregiSyncInboundTransactionsJob::class, function (SmaregiSyncInboundTransactionsJob $job): bool {
            return $job->contractId === self::CONTRACT_ID
                && $job->transactionHeadIds === ['39'];
        });
    }

    /** @return array<string, array{bool}> */
    public static function executionModes(): array
    {
        return ['after response' => [false], 'inline webhook' => [true]];
    }

    #[DataProvider('executionModes')]
    public function test_通常販売取引が来店履歴と購入額として取り込まれること(bool $inline): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
        ]);
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'smaregi_product_id' => '8000205',
            'name' => 'テストケーキ',
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '39',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => '259',
            'total' => '2046',
            'terminalTranDateTime' => '2026-07-19T01:44:25+09:00',
            'transactionDateTime' => '2026-07-19T01:44:26+09:00',
            'details' => [[
                'transactionDetailId' => '1',
                'productId' => '8000205',
                'productName' => 'フリーライト チョコレートケーキ',
                'quantity' => '3',
                'salesPrice' => '682',
                'unitDiscountedSum' => '2046',
            ]],
        ]);

        $this->syncTransaction('39', $inline);

        $this->assertDatabaseHas('member_visits', [
            'member_id' => $member->id,
            'project_id' => $project->id,
            'source' => MemberVisitSource::SMAREGI,
            'smaregi_transaction_head_id' => '39',
            'amount' => 2046,
        ]);
        $this->assertDatabaseHas('member_visit_items', [
            'product_id' => $product->id,
            'smaregi_product_id' => '8000205',
            'product_name' => 'フリーライト チョコレートケーキ',
            'quantity' => 3,
            'amount' => 2046,
        ]);

        $member->refresh();
        $this->assertSame('2026-07-19', $member->last_visited_at?->format('Y-m-d'));

        $summary = app(MemberAdminQueryService::class)->getPurchaseSummary($member->fresh());
        $this->assertSame(2046.0, $summary['total_revenue']);
        $this->assertSame(1, $summary['total_orders']);

        $this->assertDatabaseHas('orders', [
            'project_id' => $project->id,
            'user_id' => $user->id,
            'order_type' => OrderType::POS,
            'status' => OrderStatus::COMPLETED,
            'smaregi_transaction_head_id' => '39',
            'total_amount' => 2046,
        ]);
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'smaregi_product_id' => '8000205',
            'product_name' => 'フリーライト チョコレートケーキ',
            'quantity' => 3,
        ]);
        $visit = MemberVisit::query()
            ->where('smaregi_transaction_head_id', '39')
            ->first();
        $this->assertNotNull($visit);
        $this->assertNotNull($visit->order_id);
    }

    public function test_reggit起点の取引は来店もポイントも二重に取り込まないこと(): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        app(ShopSettingStore::class)->setValue('point_earn_rate', 1, 'integer', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
            'points' => 0,
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'total_amount' => 2046,
            'status' => OrderStatus::COMPLETED,
            'payment_method' => PaymentMethod::CASH,
            'points_used' => 0,
            'points_earned' => 20,
            'order_type' => OrderType::MOBILE,
            'smaregi_transaction_head_id' => '39',
            'smaregi_transaction_synced_at' => now(),
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '39',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => '259',
            'total' => '2046',
            'terminalTranId' => 'RG'.$order->id,
            'terminalTranDateTime' => '2026-07-19T01:44:25+09:00',
            'transactionDateTime' => '2026-07-19T01:44:26+09:00',
            'details' => [[
                'transactionDetailId' => '1',
                'productId' => '8000205',
                'productName' => 'ケーキ',
                'quantity' => '1',
                'salesPrice' => '2046',
                'unitDiscountedSum' => '2046',
            ]],
        ]);

        app(SmaregiTransactionInboundSyncService::class)
            ->syncByTransactionHeadIds(self::CONTRACT_ID, ['39']);

        $this->assertSame(0, MemberVisit::query()->where('smaregi_transaction_head_id', '39')->count());
        $this->assertSame(0, PointTransaction::query()->where('member_id', $member->id)->count());
        $this->assertSame(1, Order::query()->where('smaregi_transaction_head_id', '39')->count());
    }

    public function test_通常販売取引でスタンプ設定がある場合はスタンプが付与されること(): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        app(ShopSettingStore::class)->setValue('stamp_earn_per_amount', 100, 'integer', (int) $project->id);
        $stampCard = StampCard::factory()->create([
            'project_id' => $project->id,
            'required_stamps' => 10,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '39',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => '259',
            'total' => '2046',
            'terminalTranDateTime' => '2026-07-19T01:44:25+09:00',
        ]);

        $service = app(SmaregiTransactionInboundSyncService::class);
        $service->syncByTransactionHeadIds(self::CONTRACT_ID, ['39']);
        $service->syncByTransactionHeadIds(self::CONTRACT_ID, ['39']);

        $this->assertDatabaseHas('member_stamp_transactions', [
            'member_id' => $member->id,
            'project_id' => $project->id,
            'stamp_card_id' => $stampCard->id,
            'delta' => 20,
            'description' => 'スマレジ取引によるスタンプ付与 #39',
        ]);
        $this->assertSame(1, MemberStampTransaction::query()->where('member_id', $member->id)->count());
        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'stamp_card_id' => $stampCard->id,
            'current_stamps' => 0,
            'cycle_count' => 2,
        ]);
    }

    public function test_通常販売取引でポイント付与レートがある場合はポイントが付与されること(): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        app(ShopSettingStore::class)->setValue('point_earn_rate', 100, 'integer', (int) $project->id);
        app(ShopSettingStore::class)->setValue('points_valid_days', 365, 'integer', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
            'rank' => 'bronze',
            'points' => 0,
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '39',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => '259',
            'total' => '2046',
            'terminalTranDateTime' => '2026-07-19T01:44:25+09:00',
        ]);

        $service = app(SmaregiTransactionInboundSyncService::class);
        $service->syncByTransactionHeadIds(self::CONTRACT_ID, ['39']);
        $service->syncByTransactionHeadIds(self::CONTRACT_ID, ['39']);

        $this->assertDatabaseHas('point_transactions', [
            'member_id' => $member->id,
            'project_id' => $project->id,
            'points' => 20,
            'description' => 'スマレジ取引によるポイント付与 #39',
        ]);
        $order = Order::query()
            ->where('project_id', $project->id)
            ->where('smaregi_transaction_head_id', '39')
            ->first();
        $this->assertNotNull($order);
        $this->assertDatabaseHas('point_transactions', [
            'member_id' => $member->id,
            'order_id' => $order->id,
            'description' => 'スマレジ取引によるポイント付与 #39',
        ]);
        $this->assertSame(1, PointTransaction::query()->where('member_id', $member->id)->count());
        $this->assertSame(20, (int) $member->fresh()->points);
    }

    public function test_スタンプ未設定の場合はスタンプを付与しないこと(): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        app(ShopSettingStore::class)->setValue('stamp_earn_per_amount', 0, 'integer', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '39',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => '259',
            'total' => '2046',
            'terminalTranDateTime' => '2026-07-19T01:44:25+09:00',
        ]);

        app(SmaregiTransactionInboundSyncService::class)
            ->syncByTransactionHeadIds(self::CONTRACT_ID, ['39']);

        $this->assertSame(0, MemberStampTransaction::query()->count());
    }

    public function test_同一取引は冪等で二重登録されないこと(): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '39',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => '259',
            'total' => '2046',
            'terminalTranDateTime' => '2026-07-19T01:44:25+09:00',
        ]);

        $service = app(SmaregiTransactionInboundSyncService::class);
        $service->syncByTransactionHeadIds(self::CONTRACT_ID, ['39']);
        $service->syncByTransactionHeadIds(self::CONTRACT_ID, ['39']);

        $this->assertSame(1, MemberVisit::query()->where('smaregi_transaction_head_id', '39')->count());
    }

    public function test_ポイント加算取引は来店履歴に取り込まないこと(): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '263',
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '38',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::POINT_ADD,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => '263',
            'total' => null,
            'totalPoint' => '2000',
            'terminalTranDateTime' => '2026-07-19T01:27:49+09:00',
        ]);

        app(SmaregiTransactionInboundSyncService::class)
            ->syncByTransactionHeadIds(self::CONTRACT_ID, ['38']);

        $this->assertSame(0, MemberVisit::query()->count());
    }

    public function test_会員なし取引は来店履歴に取り込まないこと(): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '40',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => null,
            'total' => '528',
            'terminalTranDateTime' => '2026-07-19T01:50:02+09:00',
        ]);

        app(SmaregiTransactionInboundSyncService::class)
            ->syncByTransactionHeadIds(self::CONTRACT_ID, ['40']);

        $this->assertSame(0, MemberVisit::query()->count());
    }

    #[DataProvider('executionModes')]
    public function test_取消取引で来店とポイントとスタンプが巻き戻されること(bool $inline): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        app(ShopSettingStore::class)->setValue('point_earn_rate', 100, 'integer', (int) $project->id);
        app(ShopSettingStore::class)->setValue('points_valid_days', 365, 'integer', (int) $project->id);
        app(ShopSettingStore::class)->setValue('stamp_earn_per_amount', 100, 'integer', (int) $project->id);
        $stampCard = StampCard::factory()->create([
            'project_id' => $project->id,
            'required_stamps' => 10,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
            'points' => 0,
        ]);

        $sale = [
            'transactionHeadId' => '39',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => '259',
            'total' => '2046',
            'terminalTranDateTime' => '2026-07-19T01:44:25+09:00',
        ];
        $this->fakeTransactionHttp($sale);

        $service = app(SmaregiTransactionInboundSyncService::class);
        $service->syncByTransactionHeadIds(self::CONTRACT_ID, ['39']);
        $this->assertSame(20, (int) $member->fresh()->points);
        $this->assertSame(1, MemberVisit::query()->where('smaregi_transaction_head_id', '39')->count());

        $cancelled = $sale;
        $cancelled['cancelDivision'] = SmaregiCancelDivision::CANCELLED;
        $this->fakeTransactionHttp($cancelled);
        $this->syncTransaction('39', $inline);
        $this->syncTransaction('39', $inline);

        $this->assertSame(0, MemberVisit::query()->where('smaregi_transaction_head_id', '39')->count());
        $this->assertSame(0, (int) $member->fresh()->points);
        $this->assertDatabaseHas('point_transactions', [
            'member_id' => $member->id,
            'description' => 'スマレジ取引取消によるポイント取消 #39',
            'type' => PointTransactionType::REVERSED,
        ]);
        $this->assertDatabaseHas('member_stamp_transactions', [
            'member_id' => $member->id,
            'description' => 'スマレジ取引取消によるスタンプ取消 #39',
            'delta' => -20,
        ]);
        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'stamp_card_id' => $stampCard->id,
            'current_stamps' => 0,
            'cycle_count' => 0,
        ]);
        $this->assertSame(
            1,
            PointTransaction::query()
                ->where('member_id', $member->id)
                ->where('description', 'スマレジ取引取消によるポイント取消 #39')
                ->count()
        );
        $this->assertDatabaseHas('orders', [
            'smaregi_transaction_head_id' => '39',
            'status' => OrderStatus::CANCELLED,
            'order_type' => OrderType::POS,
        ]);
    }

    public function test_返品取引でポイントとスタンプが減算され来店が負金額で残ること(): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        app(ShopSettingStore::class)->setValue('point_earn_rate', 100, 'integer', (int) $project->id);
        app(ShopSettingStore::class)->setValue('points_valid_days', 365, 'integer', (int) $project->id);
        app(ShopSettingStore::class)->setValue('stamp_earn_per_amount', 100, 'integer', (int) $project->id);
        $stampCard = StampCard::factory()->create([
            'project_id' => $project->id,
            'required_stamps' => 10,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
            'points' => 0,
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '39',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => '259',
            'total' => '2046',
            'terminalTranDateTime' => '2026-07-19T01:44:25+09:00',
        ]);
        $service = app(SmaregiTransactionInboundSyncService::class);
        $service->syncByTransactionHeadIds(self::CONTRACT_ID, ['39']);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '50',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'returnSales' => SmaregiReturnSalesDivision::RETURN,
            'customerId' => '259',
            'total' => '1000',
            'terminalTranDateTime' => '2026-07-19T02:00:00+09:00',
        ]);
        $service->syncByTransactionHeadIds(self::CONTRACT_ID, ['50']);
        $service->syncByTransactionHeadIds(self::CONTRACT_ID, ['50']);

        $this->assertDatabaseHas('member_visits', [
            'member_id' => $member->id,
            'smaregi_transaction_head_id' => '50',
            'amount' => -1000,
        ]);
        $this->assertSame(10, (int) $member->fresh()->points);
        $this->assertDatabaseHas('point_transactions', [
            'member_id' => $member->id,
            'description' => 'スマレジ取引返品によるポイント取消 #50',
        ]);
        $this->assertDatabaseHas('member_stamp_transactions', [
            'member_id' => $member->id,
            'description' => 'スマレジ取引返品によるスタンプ取消 #50',
            'delta' => -10,
        ]);
        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'stamp_card_id' => $stampCard->id,
            'current_stamps' => 0,
            'cycle_count' => 1,
        ]);
        $this->assertSame(
            1,
            PointTransaction::query()
                ->where('member_id', $member->id)
                ->where('description', 'スマレジ取引返品によるポイント取消 #50')
                ->count()
        );
    }

    public function test_sales_head_divisionが1でも通常販売としてポイント付与されること(): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        app(ShopSettingStore::class)->setValue('point_earn_rate', 100, 'integer', (int) $project->id);
        app(ShopSettingStore::class)->setValue('points_valid_days', 365, 'integer', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
            'points' => 0,
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '41',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'salesHeadDivision' => '1',
            'returnSales' => SmaregiReturnSalesDivision::NORMAL,
            'customerId' => '259',
            'total' => '1000',
            'terminalTranDateTime' => '2026-07-19T03:00:00+09:00',
        ]);

        app(SmaregiTransactionInboundSyncService::class)
            ->syncByTransactionHeadIds(self::CONTRACT_ID, ['41']);

        $this->assertDatabaseHas('member_visits', [
            'member_id' => $member->id,
            'smaregi_transaction_head_id' => '41',
            'amount' => 1000,
        ]);
        $this->assertSame(10, (int) $member->fresh()->points);
        $this->assertDatabaseHas('point_transactions', [
            'member_id' => $member->id,
            'description' => 'スマレジ取引によるポイント付与 #41',
            'points' => 10,
        ]);
    }

    public function test_返品でnew_pointがある場合はローカルポイントを減算しないこと(): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        app(ShopSettingStore::class)->setValue('point_earn_rate', 100, 'integer', (int) $project->id);
        app(ShopSettingStore::class)->setValue('points_valid_days', 365, 'integer', (int) $project->id);
        app(ShopSettingStore::class)->setValue('stamp_earn_per_amount', 100, 'integer', (int) $project->id);
        $stampCard = StampCard::factory()->create([
            'project_id' => $project->id,
            'required_stamps' => 10,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
            'points' => 50,
        ]);
        MemberStamp::factory()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'stamp_card_id' => $stampCard->id,
            'current_stamps' => 20,
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '51',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'returnSales' => SmaregiReturnSalesDivision::RETURN,
            'newPoint' => '-10',
            'customerId' => '259',
            'total' => '1000',
            'terminalTranDateTime' => '2026-07-19T04:00:00+09:00',
        ]);

        app(SmaregiTransactionInboundSyncService::class)
            ->syncByTransactionHeadIds(self::CONTRACT_ID, ['51']);

        $this->assertSame(50, (int) $member->fresh()->points);
        $this->assertSame(
            0,
            PointTransaction::query()
                ->where('member_id', $member->id)
                ->where('description', 'スマレジ取引返品によるポイント取消 #51')
                ->count()
        );
        $this->assertDatabaseHas('member_stamp_transactions', [
            'member_id' => $member->id,
            'description' => 'スマレジ取引返品によるスタンプ取消 #51',
            'delta' => -10,
        ]);
        $this->assertDatabaseHas('member_stamps', [
            'member_id' => $member->id,
            'stamp_card_id' => $stampCard->id,
            'current_stamps' => 0,
            'cycle_count' => 1,
        ]);
    }

    public function test_取引連動スタンプオフならスタンプを付与しないこと(): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        app(ShopSettingStore::class)->setValue('stamp_earn_per_amount', 100, 'integer', (int) $project->id);
        app(ShopSettingStore::class)->setValue('use_transaction_stamps', false, 'boolean', (int) $project->id);
        StampCard::factory()->create([
            'project_id' => $project->id,
            'required_stamps' => 10,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '39',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => '259',
            'total' => '2046',
            'terminalTranDateTime' => '2026-07-19T01:44:25+09:00',
        ]);

        app(SmaregiTransactionInboundSyncService::class)
            ->syncByTransactionHeadIds(self::CONTRACT_ID, ['39']);

        $this->assertSame(0, MemberStampTransaction::query()->where('member_id', $member->id)->count());
    }

    public function test_ポイント機能オフならポイントを付与しないこと(): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        app(ShopSettingStore::class)->setValue('point_earn_rate', 100, 'integer', (int) $project->id);
        app(ShopSettingStore::class)->setValue('use_points', false, 'boolean', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
            'rank' => 'bronze',
            'points' => 0,
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '39',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => '259',
            'total' => '2046',
            'terminalTranDateTime' => '2026-07-19T01:44:25+09:00',
        ]);

        app(SmaregiTransactionInboundSyncService::class)
            ->syncByTransactionHeadIds(self::CONTRACT_ID, ['39']);

        $this->assertSame(0, PointTransaction::query()->where('member_id', $member->id)->count());
    }

    public function test_ランク別スタンプ付与レートで付与すること(): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        app(ShopSettingStore::class)->setValue('stamp_earn_per_amount', 1000, 'integer', (int) $project->id);
        app(MemberRankSettingStore::class)->setMemberRankSettings([
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true, 'stamp_earn_per_amount' => 500],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 100, 'order' => 2, 'is_active' => true, 'stamp_earn_per_amount' => 200],
        ], (int) $project->id);
        StampCard::factory()->create([
            'project_id' => $project->id,
            'required_stamps' => 20,
            'is_active' => true,
        ]);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
            'rank' => 'gold',
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '39',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => '259',
            'total' => '1000',
            'terminalTranDateTime' => '2026-07-19T01:44:25+09:00',
        ]);

        app(SmaregiTransactionInboundSyncService::class)
            ->syncByTransactionHeadIds(self::CONTRACT_ID, ['39']);

        $this->assertDatabaseHas('member_stamp_transactions', [
            'member_id' => $member->id,
            'delta' => 5,
            'description' => 'スマレジ取引によるスタンプ付与 #39',
        ]);
    }

    public function test_取引付与後にランクが上がること(): void
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        app(ShopSettingStore::class)->setValue('point_earn_rate', 100, 'integer', (int) $project->id);
        app(ShopSettingStore::class)->setValue('points_valid_days', 365, 'integer', (int) $project->id);
        app(MemberRankSettingStore::class)->setMemberRankSettings([
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 10, 'order' => 2, 'is_active' => true],
        ], (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
            'rank' => 'bronze',
            'points' => 0,
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '39',
            'transactionHeadDivision' => SmaregiTransactionHeadDivision::NORMAL,
            'cancelDivision' => SmaregiCancelDivision::NORMAL,
            'customerId' => '259',
            'total' => '2046',
            'terminalTranDateTime' => '2026-07-19T01:44:25+09:00',
        ]);

        app(SmaregiTransactionInboundSyncService::class)
            ->syncByTransactionHeadIds(self::CONTRACT_ID, ['39']);

        $this->assertSame('gold', $member->fresh()->rank);
    }

    private function syncTransaction(string $headId, bool $inline): void
    {
        if (! $inline) {
            app(SmaregiTransactionInboundSyncService::class)->syncByTransactionHeadIds(self::CONTRACT_ID, [$headId]);

            return;
        }
        config(['smaregi.webhook_run_sync_inline' => true]);
        $this->postJson('/api/smaregi/webhook', [
            'contractId' => self::CONTRACT_ID,
            'event' => SmaregiEvent::POS_TRANSACTIONS,
            'action' => SmaregiWebhookAction::CREATED,
            'transactionHeadIds' => [$headId],
        ], [
            'smaregi-event' => SmaregiEvent::POS_TRANSACTIONS,
            'smaregi-contract-id' => self::CONTRACT_ID,
        ])->assertOk();
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function fakeTransactionHttp(array $transaction): void
    {
        $headId = trim((string) ($transaction['transactionHeadId'] ?? ''));
        if ($headId !== '') {
            $this->transactionHttpFixtures[$headId] = $transaction;
        }

        // Http::fake は後勝ちではなく先勝ちのため、常に fixtures を参照するコールバックにする
        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'id.smaregi.dev') && str_contains($url, '/token')) {
                return Http::response(['access_token' => 'fake', 'expires_in' => 3600], 200);
            }
            if (preg_match('#/pos/transactions/([^/?]+)#', $url, $matches) === 1) {
                $requestedHeadId = rawurldecode($matches[1]);
                $payload = $this->transactionHttpFixtures[$requestedHeadId] ?? null;
                if (is_array($payload)) {
                    return Http::response($payload, 200);
                }
            }

            return Http::response([], 404);
        });
    }
}
