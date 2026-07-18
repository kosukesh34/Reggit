<?php

namespace Tests\Feature;

use App\Enums\MemberVisitSource;
use App\Jobs\SmaregiSyncInboundTransactionsJob;
use App\Models\Member;
use App\Models\MemberVisit;
use App\Models\Product;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\Admin\MemberAdminService;
use App\Services\Smaregi\SmaregiTransactionInboundSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmaregiInboundTransactionHistoryTest extends TestCase
{
    use RefreshDatabase;

    private const CONTRACT_ID = 'test_contract_tx_history';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
            'smaregi.contract_id' => '',
        ]);
    }

    public function test_webhook_pos_transactionsで取引履歴同期ジョブがディスパッチされること(): void
    {
        Bus::fake();

        $this->postJson('/api/smaregi/webhook', [
            'contractId' => self::CONTRACT_ID,
            'event' => 'pos:transactions',
            'action' => 'created',
            'transactionHeadIds' => ['39'],
        ], [
            'smaregi-event' => 'pos:transactions',
            'smaregi-contract-id' => self::CONTRACT_ID,
            'Content-Type' => 'application/json',
        ])->assertStatus(200);

        Bus::assertDispatched(SmaregiSyncInboundTransactionsJob::class, function (SmaregiSyncInboundTransactionsJob $job): bool {
            return $job->contractId === self::CONTRACT_ID
                && $job->transactionHeadIds === ['39'];
        });
    }

    public function test_通常販売取引が来店履歴と購入額として取り込まれること(): void
    {
        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
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
            'transactionHeadDivision' => '1',
            'cancelDivision' => '0',
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

        app(SmaregiTransactionInboundSyncService::class)
            ->syncByTransactionHeadIds(self::CONTRACT_ID, ['39']);

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

        $summary = app(MemberAdminService::class)->getPurchaseSummary($member->fresh());
        $this->assertSame(2046.0, $summary['total_revenue']);
        $this->assertSame(1, $summary['total_orders']);
    }

    public function test_同一取引は冪等で二重登録されないこと(): void
    {
        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '259',
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '39',
            'transactionHeadDivision' => '1',
            'cancelDivision' => '0',
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
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '263',
        ]);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '38',
            'transactionHeadDivision' => '6',
            'cancelDivision' => '0',
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
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $this->fakeTransactionHttp([
            'transactionHeadId' => '40',
            'transactionHeadDivision' => '1',
            'cancelDivision' => '0',
            'customerId' => null,
            'total' => '528',
            'terminalTranDateTime' => '2026-07-19T01:50:02+09:00',
        ]);

        app(SmaregiTransactionInboundSyncService::class)
            ->syncByTransactionHeadIds(self::CONTRACT_ID, ['40']);

        $this->assertSame(0, MemberVisit::query()->count());
    }

    /**
     * @param  array<string, string|int|float|bool|null>  $transaction
     */
    private function fakeTransactionHttp(array $transaction): void
    {
        Http::fake(function ($request) use ($transaction) {
            $url = $request->url();
            if (str_contains($url, 'id.smaregi.dev') && str_contains($url, '/token')) {
                return Http::response(['access_token' => 'fake', 'expires_in' => 3600], 200);
            }
            if (str_contains($url, self::CONTRACT_ID.'/pos/transactions/')) {
                return Http::response($transaction, 200);
            }

            return Http::response([], 404);
        });
    }
}
