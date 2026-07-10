<?php

namespace Tests\Feature;

use App\Jobs\SmaregiSyncInboundCustomerPointsJob;
use App\Models\Member;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\MemberService;
use App\Services\Smaregi\SmaregiCustomerPointsInboundSyncService;
use App\Services\Smaregi\SmaregiPointsSyncService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\Support\GrantsMemberPoints;
use Tests\TestCase;

class SmaregiInboundCustomerPointsSyncTest extends TestCase
{
    use GrantsMemberPoints;
    use RefreshDatabase;

    private const CONTRACT_ID = 'test_contract_inbound';

    protected function setUp(): void
    {
        parent::setUp();
        AdminContext::setCurrentProject(null);
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
        ]);
    }

    public function test_webhook_pos_customersで同期ジョブがディスパッチされること(): void
    {
        Bus::fake();

        $this->postJson('/api/smaregi/webhook', [
            'contractId' => self::CONTRACT_ID,
            'event' => 'pos:customers',
            'action' => 'edited',
            'customerIds' => ['7', '8'],
        ], [
            'smaregi-event' => 'pos:customers',
            'smaregi-contract-id' => self::CONTRACT_ID,
            'Content-Type' => 'application/json',
        ])->assertStatus(200);

        Bus::assertDispatched(SmaregiSyncInboundCustomerPointsJob::class, function (SmaregiSyncInboundCustomerPointsJob $job): bool {
            return $job->contractId === self::CONTRACT_ID
                && $job->customerIds === ['7', '8']
                && $job->action === 'edited';
        });
    }

    public function test_pos_customersの同期でスマレジのポイントがローカルに反映されること(): void
    {
        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'M001',
            'smaregi_customer_id' => '42',
            'points' => 0,
        ]);
        $this->grantMemberPoints($member, 40, '初期付与');

        $this->fakeSmaregiHttpSingleCustomer([
            'customerId' => '42',
            'customerCode' => 'M001',
            'point' => '55',
        ]);

        $service = app(SmaregiCustomerPointsInboundSyncService::class);
        $service->syncByCustomerIds(self::CONTRACT_ID, ['42'], 'edited');

        $member->refresh();
        $this->assertSame(55, $member->points);
        $this->assertSame(55, $member->getTotalPointsFromTransactions());
    }

    public function test_outbound_pending中は_webhook取込でポイントを巻き戻さないこと(): void
    {
        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'M-PENDING',
            'smaregi_customer_id' => '42',
            'points' => 0,
        ]);
        $this->grantMemberPoints($member, 100, 'ローカル先行');

        app(SmaregiPointsSyncService::class)
            ->markOutboundPending((int) $member->id);

        $this->fakeSmaregiHttpSingleCustomer([
            'customerId' => '42',
            'customerCode' => 'M-PENDING',
            'point' => '40',
        ]);

        app(SmaregiCustomerPointsInboundSyncService::class)
            ->syncByCustomerIds(self::CONTRACT_ID, ['42'], 'edited');

        $member->refresh();
        $this->assertSame(100, $member->points);
        $this->assertSame(100, $member->getTotalPointsFromTransactions());
    }

    public function test_pos_customersの同期でスマレジの会員情報がローカルに反映されること(): void
    {
        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $user = User::factory()->create([
            'project_id' => $project->id,
            'name' => '旧姓 旧名',
            'kana' => 'キュウセイ キュウメイ',
            'email' => 'old@example.com',
            'phone' => '09000000000',
        ]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'OLD001',
            'smaregi_customer_id' => '43',
            'points' => 0,
            'address' => '旧住所',
        ]);

        $this->fakeSmaregiHttpSingleCustomer([
            'customerId' => '43',
            'customerCode' => 'NEW001',
            'lastName' => '山田',
            'firstName' => '太郎',
            'lastKana' => 'ヤマダ',
            'firstKana' => 'タロウ',
            'mailAddress' => 'new@example.com',
            'phoneNumber' => '09011112222',
            'postCode' => '1000001',
            'address' => '東京都千代田区千代田1-1',
            'birthDate' => '1990-01-02',
            'sex' => '1',
            'point' => '20',
        ]);

        $service = app(SmaregiCustomerPointsInboundSyncService::class);
        $service->syncByCustomerIds(self::CONTRACT_ID, ['43'], 'edited');

        $member->refresh();
        $user->refresh();
        $this->assertSame('NEW001', $member->member_number);
        $this->assertSame('山田 太郎', $user->name);
        $this->assertSame('ヤマダ タロウ', $user->kana);
        $this->assertSame('new@example.com', $user->email);
        $this->assertSame('09011112222', $user->phone);
        $this->assertSame('1000001', $member->postal_code);
        $this->assertSame('東京都千代田区千代田1-1', $member->address);
        $this->assertSame('1990-01-02', $member->birthday->format('Y-m-d'));
        $this->assertSame('1', $member->sex);
        $this->assertSame(20, $member->points);
    }

    public function test_他プロジェクトの同一会員番号があってもcustomerCodeを反映できること(): void
    {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $otherUser = User::factory()->create(['project_id' => $otherProject->id]);
        Member::factory()->create([
            'project_id' => $otherProject->id,
            'user_id' => $otherUser->id,
            'member_number' => 'NEW001',
            'smaregi_customer_id' => 'other-cust',
            'points' => 0,
        ]);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'OLD001',
            'smaregi_customer_id' => '43',
            'points' => 0,
        ]);

        $this->fakeSmaregiHttpSingleCustomer([
            'customerId' => '43',
            'customerCode' => 'NEW001',
            'point' => '0',
        ]);

        app(SmaregiCustomerPointsInboundSyncService::class)
            ->syncByCustomerIds(self::CONTRACT_ID, ['43'], 'edited');

        $this->assertSame('NEW001', $member->fresh()->member_number);
    }

    public function test_members_me取得時にスマレジのポイントが取り込まれること(): void
    {
        config(['smaregi.inbound_sync_on_member_fetch' => true, 'smaregi.inbound_sync_on_member_fetch_ttl_seconds' => 0]);

        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'M-FETCH',
            'smaregi_customer_id' => '88',
            'points' => 0,
        ]);
        $this->grantMemberPoints($member, 10, '初期');

        $this->fakeSmaregiHttpSingleCustomer([
            'customerId' => '88',
            'customerCode' => 'M-FETCH',
            'point' => '120',
        ]);

        AdminContext::setCurrentProject($project);
        $data = app(MemberService::class)->getMemberData($user->fresh(['member']));

        $member->refresh();
        $this->assertTrue($data['is_member']);
        $this->assertSame(120, $data['points']);
        $this->assertSame(120, $member->points);
    }

    public function test_sync_member_from_smaregiで会員番号からスマレジidを紐付けて同期できること(): void
    {
        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'LINK001',
            'smaregi_customer_id' => null,
            'points' => 0,
        ]);

        Http::fake(function ($request) {
            $url = $request->url();
            $method = strtoupper($request->method());
            if (str_contains($url, 'id.smaregi.dev') && str_contains($url, '/token')) {
                return Http::response(['access_token' => 'fake', 'expires_in' => 3600], 200);
            }
            if ($method === 'GET' && str_contains($url, '/pos/customers') && str_contains($url, 'customer_code')) {
                return Http::response([
                    ['customerId' => '55', 'customerCode' => 'LINK001', 'point' => '77'],
                ], 200);
            }
            if ($method === 'GET' && preg_match('#/pos/customers/55/points#', $url)) {
                return Http::response(['customerId' => '55', 'customerCode' => 'LINK001', 'point' => '77'], 200);
            }
            if ($method === 'GET' && preg_match('#/pos/customers/point#', $url)) {
                return Http::response([['customerId' => '55', 'customerCode' => 'LINK001', 'point' => '77']], 200);
            }
            if ($method === 'GET' && preg_match('#/pos/customers/55#', $url)) {
                return Http::response(['customerId' => '55', 'customerCode' => 'LINK001', 'point' => '77'], 200);
            }

            return Http::response([], 404);
        });

        $service = app(SmaregiCustomerPointsInboundSyncService::class);
        $service->syncMemberFromSmaregi($member);

        $member->refresh();
        $this->assertSame('55', $member->smaregi_customer_id);
        $this->assertSame(77, $member->points);
    }

    public function test_configフォールバック契約でもプロジェクトに紐づいて同期できること(): void
    {
        config(['smaregi.contract_id' => 'env_fallback_contract']);

        $project = Project::factory()->create();
        // smaregi_contract_id は未設定（NULL）のまま

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '93',
            'points' => 0,
        ]);
        $this->grantMemberPoints($member, 0, '初期');

        $this->fakeSmaregiHttpSingleCustomer([
            'customerId' => '93',
            'customerCode' => 'M093',
            'point' => '500',
        ], 'env_fallback_contract');

        $service = app(SmaregiCustomerPointsInboundSyncService::class);
        $service->syncByCustomerIds('env_fallback_contract', ['93'], 'edited');

        $member->refresh();
        $this->assertSame(500, $member->points);
    }

    public function test_webhook_pos_customer_pointsでtransaction_headから同期ジョブがディスパッチされること(): void
    {
        Bus::fake();

        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'id.smaregi.dev') && str_contains($url, '/token')) {
                return Http::response(['access_token' => 'fake', 'expires_in' => 3600], 200);
            }
            if (str_contains($url, self::CONTRACT_ID.'/pos/transactions/555')) {
                return Http::response(['transactionHeadId' => '555', 'customerId' => '93'], 200);
            }

            return Http::response([], 404);
        });

        $this->postJson('/api/smaregi/webhook', [
            'contractId' => self::CONTRACT_ID,
            'event' => 'pos:customer-points',
            'action' => 'edited',
            'transactionHeadIds' => ['555'],
        ], [
            'smaregi-event' => 'pos:customer-points',
            'smaregi-contract-id' => self::CONTRACT_ID,
            'Content-Type' => 'application/json',
        ])->assertStatus(200);

        Bus::assertDispatched(SmaregiSyncInboundCustomerPointsJob::class, function (SmaregiSyncInboundCustomerPointsJob $job): bool {
            return $job->contractId === self::CONTRACT_ID
                && $job->customerIds === ['93']
                && $job->action === 'edited';
        });
    }

    public function test_deletedアクションでは同期しないこと(): void
    {
        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '99',
            'points' => 0,
        ]);
        $this->grantMemberPoints($member, 10, '初期');

        $this->fakeSmaregiHttpSingleCustomer(['customerId' => '99', 'point' => '999']);

        $service = app(SmaregiCustomerPointsInboundSyncService::class);
        $service->syncByCustomerIds(self::CONTRACT_ID, ['99'], 'deleted');

        $member->refresh();
        $this->assertSame(10, $member->points);
    }

    public function test_point_nullの場合は0として同期されること(): void
    {
        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '100',
            'points' => 0,
        ]);
        $this->grantMemberPoints($member, 50, '初期');
        $member->refresh();
        $this->assertSame(50, $member->points);

        $this->fakeSmaregiHttpSingleCustomer([
            'customerId' => '100',
            'customerCode' => 'M100',
            'point' => null,
            'mile' => null,
        ]);

        $service = app(SmaregiCustomerPointsInboundSyncService::class);
        $service->syncByCustomerIds(self::CONTRACT_ID, ['100'], 'edited');

        $member->refresh();
        $this->assertSame(0, $member->points);
        $this->assertSame(0, $member->getTotalPointsFromTransactions());
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $customerPayload
     */
    /**
     * @param  array<string, string|int|float|bool|array|null>  $customerPayload
     */
    private function fakeSmaregiHttpSingleCustomer(array $customerPayload, ?string $contractId = null): void
    {
        $contractId = $contractId ?? self::CONTRACT_ID;
        $idBase = 'https://id.smaregi.dev';
        $apiBase = 'https://api.smaregi.dev';

        Http::fake(function ($request) use ($idBase, $apiBase, $customerPayload, $contractId) {
            $url = $request->url();
            $method = strtoupper($request->method());

            if (str_contains($url, $idBase) && str_contains($url, '/token')) {
                return Http::response([
                    'access_token' => 'fake_access_token_'.$contractId,
                    'expires_in' => 3600,
                ], 200);
            }

            if (! str_contains($url, $apiBase) || ! str_contains($url, $contractId)) {
                return Http::response([], 404);
            }

            if ($method === 'GET' && preg_match('#/pos/customers/[^/]+/points($|\?)#', $url)) {
                return Http::response($customerPayload, 200);
            }

            if ($method === 'GET' && preg_match('#/pos/customers/point($|\?)#', $url)) {
                return Http::response([$customerPayload], 200);
            }

            if ($method === 'GET' && preg_match('#/pos/customers/[^/]+($|\?)#', $url)) {
                return Http::response($customerPayload, 200);
            }

            return Http::response([], 404);
        });
    }
}
