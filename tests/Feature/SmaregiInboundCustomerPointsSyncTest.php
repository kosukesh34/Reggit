<?php

namespace Tests\Feature;

use App\Domain\Order\Events\OrderPointsSyncRequested;
use App\Domain\Shared\DomainEventDispatcher\DomainEventDispatcher;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Jobs\SmaregiSyncInboundCustomerPointsJob;
use App\Models\Member;
use App\Models\Order;
use App\Models\Project;
use App\Models\Store;
use App\Models\User;
use App\Services\MemberService;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerPointsInboundSyncService;
use App\Services\Smaregi\SmaregiPointsSyncService;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
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
        Cache::flush();
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
            'smaregi.contract_id' => '',
            'smaregi.webhook_secret' => '',
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
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

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
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

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

    public function test_another_successful_order_cannot_expose_pending_points_to_inbound(): void
    {
        $project = Project::factory()->create();
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id, 'user_id' => $user->id, 'smaregi_customer_id' => '42', 'points' => 0,
        ]);
        $this->grantMemberPoints($member, 150);
        $orders = [];
        foreach ([100, 50] as $points) {
            $orders[] = Order::create([
                'project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 1000,
                'status' => OrderStatus::CONFIRMED, 'payment_method' => PaymentMethod::CASH,
                'points_earned' => $points, 'points_used' => 0, 'order_type' => 'mobile',
            ]);
        }
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn(self::CONTRACT_ID);
        $api->shouldReceive('syncCustomerPointsForProject')->once()->with((int) $project->id, '42', 100)->andReturnNull();
        $api->shouldReceive('syncCustomerPointsForProject')->once()->with((int) $project->id, '42', 50)->andReturn([]);
        $api->shouldReceive('getCustomer')->andReturn(['customerId' => '42']);
        $api->shouldReceive('getCustomerPoint')->andReturn(['point' => 50]);
        $events = $this->mock(DomainEventDispatcher::class);
        $events->shouldReceive('dispatch')->once()->withArgs(fn ($event) => $event instanceof OrderPointsSyncRequested
            && $event->orderId === (int) $orders[0]->id);
        $sync = app(SmaregiPointsSyncService::class);
        $this->assertFalse($sync->syncOrderNetPoints((int) $orders[0]->id, (int) $project->id, (int) $user->id));
        $this->assertTrue($sync->syncOrderNetPoints((int) $orders[1]->id, (int) $project->id, (int) $user->id));
        $this->assertNull($orders[0]->fresh()->smaregi_points_synced_at);
        $this->assertNotNull($orders[1]->fresh()->smaregi_points_synced_at);
        $this->assertFalse($sync->hasOutboundPending((int) $member->id));

        app(SmaregiCustomerPointsInboundSyncService::class)->syncMemberFromSmaregi($member);

        $this->assertSame(150, $member->fresh()->getTotalPointsFromTransactions());
        $this->assertSame(0, $member->pointTransactions()->where('description', 'スマレジPOSからの同期')->count());
    }

    public function test_order_ack_during_remote_fetch_cannot_apply_the_old_point_balance(): void
    {
        $this->assertRemoteFetchCannotUndoOrderAcknowledgedDuringFetch(true);
    }

    public function test_order_created_and_acknowledged_during_remote_fetch_cannot_apply_the_old_point_balance(): void
    {
        $this->assertRemoteFetchCannotUndoOrderAcknowledgedDuringFetch(false);
    }

    public function test_customer_relinked_during_fetch_keeps_local_profile_and_points(): void
    {
        $project = Project::factory()->create();
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id, 'name' => 'Local']);
        $member = Member::factory()->create([
            'project_id' => $project->id, 'user_id' => $user->id, 'smaregi_customer_id' => '42', 'points' => 0,
        ]);
        $this->grantMemberPoints($member, 100);
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn(self::CONTRACT_ID);
        $api->shouldReceive('getCustomer')->once()->andReturn(['customerId' => '42', 'lastName' => 'Old remote', 'firstName' => '']);
        $api->shouldReceive('getCustomerPoint')->once()->andReturnUsing(function () use ($member): array {
            $member->fresh()->update(['smaregi_customer_id' => '43']);

            return ['point' => 55];
        });
        $this->mock(DomainEventDispatcher::class)->shouldNotReceive('dispatch');

        app(SmaregiCustomerPointsInboundSyncService::class)->syncMemberFromSmaregi($member);

        $this->assertSame('43', $member->fresh()->smaregi_customer_id);
        $this->assertSame('Local', $user->fresh()->name);
        $this->assertSame(100, $member->fresh()->getTotalPointsFromTransactions());
        $this->assertSame(0, $member->pointTransactions()->where('description', 'スマレジPOSからの同期')->count());
    }

    private function assertRemoteFetchCannotUndoOrderAcknowledgedDuringFetch(bool $existingOrder): void
    {
        $project = Project::factory()->create();
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id, 'user_id' => $user->id, 'smaregi_customer_id' => '42', 'points' => 0,
        ]);
        $createOrder = function () use ($project, $user, $member): Order {
            $this->grantMemberPoints($member, 100);

            return Order::create([
                'project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 1000,
                'status' => OrderStatus::CONFIRMED, 'payment_method' => PaymentMethod::CASH,
                'points_earned' => 100, 'points_used' => 0, 'order_type' => 'mobile',
            ]);
        };
        $order = $existingOrder ? $createOrder() : null;
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn(self::CONTRACT_ID);
        $api->shouldReceive('syncCustomerPointsForProject')->once()->with((int) $project->id, '42', 100)->andReturn([]);
        $api->shouldReceive('getCustomer')->andReturn(['customerId' => '42']);
        $synced = false;
        $api->shouldReceive('getCustomerPoint')->once()->andReturnUsing(function () use (&$order, &$synced, $createOrder, $project, $user): array {
            $order ??= $createOrder();
            $synced = app(SmaregiPointsSyncService::class)->syncOrderNetPoints((int) $order->id, (int) $project->id, (int) $user->id);

            // This response was fetched before the outbound +100 completed.
            return ['point' => 0];
        });
        $this->mock(DomainEventDispatcher::class)->shouldNotReceive('dispatch');

        app(SmaregiCustomerPointsInboundSyncService::class)->syncMemberFromSmaregi($member);

        $this->assertTrue($synced);
        $this->assertNotNull($order?->fresh()->smaregi_points_synced_at);
        $this->assertSame(100, $member->fresh()->getTotalPointsFromTransactions());
        $this->assertSame(0, $member->pointTransactions()->where('description', 'スマレジPOSからの同期')->count());
    }

    public function test_pending_points_in_another_store_survive_cache_eviction(): void
    {
        $project = Project::factory()->create();
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id, 'user_id' => $user->id, 'smaregi_customer_id' => '42', 'points' => 0,
        ]);
        $this->grantMemberPoints($member, 100);
        $first = $project->stores()->firstOrFail();
        $second = Store::create(['project_id' => $project->id, 'store_key' => 'second', 'name' => 'Second', 'is_active' => true]);
        Order::create([
            'project_id' => $project->id, 'store_id' => $second->id, 'user_id' => $user->id, 'total_amount' => 1000,
            'status' => OrderStatus::CONFIRMED, 'payment_method' => PaymentMethod::CASH,
            'points_earned' => 100, 'points_used' => 0, 'order_type' => 'mobile',
        ]);
        Cache::flush();
        $this->fakeSmaregiHttpSingleCustomer(['customerId' => '42', 'point' => 0]);
        $this->mock(DomainEventDispatcher::class)->shouldNotReceive('dispatch');

        app(StoreContext::class)->runForStore($first, fn () => app(SmaregiCustomerPointsInboundSyncService::class)
            ->syncMemberFromSmaregi($member));

        $this->assertSame(100, $member->fresh()->getTotalPointsFromTransactions());
    }

    public function test_pos_customersの同期でスマレジの会員情報がローカルに反映されること(): void
    {
        $project = Project::factory()->create();
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $store = $project->stores()->firstOrFail();
        $store->update(['smaregi_store_id' => 'smaregi-store-12']);

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
            'storeId' => 'smaregi-store-12',
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
        $this->assertSame($store->id, (int) $member->customer_store_id);
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

    public function test_pos_customersの同期で登録時の氏名プレースホルダは取り込まないこと(): void
    {
        $project = Project::factory()->create();
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id, 'name' => null, 'kana' => 'ヤマダ']);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '44',
        ]);

        $this->fakeSmaregiHttpSingleCustomer([
            'customerId' => '44',
            'lastName' => '—',
            'firstName' => '—',
            'lastKana' => 'ヤマダ',
            'firstKana' => 'ー',
        ]);

        app(SmaregiCustomerPointsInboundSyncService::class)->syncByCustomerIds(self::CONTRACT_ID, ['44'], 'edited');

        $user->refresh();
        $this->assertNull($user->name);
        $this->assertSame('ヤマダ', $user->kana);
    }

    public function test_pos_customersの同期で任意項目がprofile_extraに反映されること(): void
    {
        $project = Project::factory()->create();
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        ShopSettingStore::setProfileFieldSettings([
            'smaregi_fields' => [],
            'custom_fields' => [
                [
                    'key' => 'nickname',
                    'label' => 'ニックネーム',
                    'required' => false,
                    'smaregi_no' => 1,
                ],
            ],
        ], (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'CF001',
            'smaregi_customer_id' => '55',
            'points' => 0,
        ]);

        $this->fakeSmaregiHttpSingleCustomer([
            'customerId' => '55',
            'customerCode' => 'CF001',
            'customFields' => [
                ['no' => 1, 'value' => 'たろう'],
            ],
            'point' => '0',
        ]);

        app(SmaregiCustomerPointsInboundSyncService::class)
            ->syncByCustomerIds(self::CONTRACT_ID, ['55'], 'edited');

        $member->refresh();
        $this->assertSame(['nickname' => 'たろう'], $member->profile_extra);
    }

    public function test_他プロジェクトの同一会員番号があってもcustomer_codeを反映できること(): void
    {
        $project = Project::factory()->create();
        $otherProject = Project::factory()->create();
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

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
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

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
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

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
                && $job->customerIds === []
                && $job->transactionHeadIds === ['555']
                && $job->action === 'edited';
        });
    }

    public function test_webhook_pos_transactionsでtransaction_headのtotal_pointをヒントに同期ジョブがディスパッチされること(): void
    {
        Bus::fake();

        $this->postJson('/api/smaregi/webhook', [
            'contractId' => self::CONTRACT_ID,
            'event' => 'pos:transactions',
            'action' => 'created',
            'transactionHeadIds' => ['777'],
        ], [
            'smaregi-event' => 'pos:transactions',
            'smaregi-contract-id' => self::CONTRACT_ID,
            'Content-Type' => 'application/json',
        ])->assertStatus(200);

        Bus::assertDispatched(SmaregiSyncInboundCustomerPointsJob::class, function (SmaregiSyncInboundCustomerPointsJob $job): bool {
            return $job->contractId === self::CONTRACT_ID
                && $job->customerIds === []
                && $job->transactionHeadIds === ['777']
                && $job->action === 'created';
        });
    }

    public function test_transaction_customer_idが後から付く場合はリトライで解決できること(): void
    {
        $attempts = 0;

        Http::fake(function ($request) use (&$attempts) {
            $url = $request->url();
            if (str_contains($url, 'id.smaregi.dev') && str_contains($url, '/token')) {
                return Http::response(['access_token' => 'fake', 'expires_in' => 3600], 200);
            }
            if (str_contains($url, self::CONTRACT_ID.'/pos/transactions/34')) {
                $attempts++;
                if ($attempts < 2) {
                    return Http::response([
                        'transactionHeadId' => '34',
                        'customerId' => null,
                        'totalPoint' => '395',
                    ], 200);
                }

                return Http::response([
                    'transactionHeadId' => '34',
                    'customerId' => '247',
                    'totalPoint' => '395',
                ], 200);
            }

            return Http::response([], 404);
        });

        $resolved = app(SmaregiApiService::class)
            ->resolveCustomersFromTransactionHeads(self::CONTRACT_ID, ['34']);

        $this->assertGreaterThanOrEqual(2, $attempts);
        $this->assertSame(['247'], $resolved['customer_ids']);
        $this->assertSame(395, $resolved['points_by_customer_id']['247'] ?? null);
    }

    public function test_resolve_customers失敗時のhttp_statusがserviceから取得できること(): void
    {
        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'id.smaregi.dev') && str_contains($url, '/token')) {
                return Http::response(['access_token' => 'fake', 'expires_in' => 3600], 200);
            }

            return Http::response(['detail' => 'boom'], 500);
        });

        $service = app(SmaregiApiService::class);
        $resolved = $service->resolveCustomersFromTransactionHeads(self::CONTRACT_ID, ['99']);

        $this->assertSame([], $resolved['customer_ids']);
        $this->assertSame(500, $service->getLastHttpStatus());
        $this->assertSame('boom', $service->getLastErrorDetail());
    }

    public function test_取引total_pointヒントがあれば会員ポイント_a_p_i失敗でも同期できること(): void
    {
        $project = Project::factory()->create();
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'smaregi_customer_id' => '253',
            'points' => 0,
        ]);
        $this->grantMemberPoints($member, 0, '初期');

        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'id.smaregi.dev') && str_contains($url, '/token')) {
                return Http::response(['access_token' => 'fake', 'expires_in' => 3600], 200);
            }
            if (preg_match('#/pos/customers/253($|\?)#', $url)) {
                return Http::response(['customerId' => '253', 'customerCode' => 'M253'], 200);
            }

            return Http::response([], 404);
        });

        $service = app(SmaregiCustomerPointsInboundSyncService::class);
        $service->syncByCustomerIds(self::CONTRACT_ID, ['253'], 'edited', ['253' => 69]);

        $member->refresh();
        $this->assertSame(69, $member->points);
    }

    public function test_deletedアクションでは同期しないこと(): void
    {
        $project = Project::factory()->create();
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

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
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

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

    public function test_inboundは_sync_points_ledger後に差分計算すること(): void
    {
        $project = Project::factory()->create();
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'M-LEDGER',
            'smaregi_customer_id' => '42',
            'points' => 100,
        ]);
        $this->assertSame(0, $member->getTotalPointsFromTransactions());

        $this->fakeSmaregiHttpSingleCustomer([
            'customerId' => '42',
            'customerCode' => 'M-LEDGER',
            'point' => '100',
        ]);

        app(SmaregiCustomerPointsInboundSyncService::class)
            ->syncByCustomerIds(self::CONTRACT_ID, ['42'], 'edited');

        $member->refresh();
        $this->assertSame(100, $member->points);
        $this->assertSame(100, $member->getTotalPointsFromTransactions());
        $this->assertSame(
            1,
            $member->pointTransactions()->where('description', Member::POINTS_LEDGER_BACKFILL_DESCRIPTION)->count()
        );
    }

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
