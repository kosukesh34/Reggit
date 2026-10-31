<?php

namespace Tests\Feature;

use App\Domain\Member\Repositories\MemberRepository;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Jobs\SmaregiSyncInboundCustomerPointsJob;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Models\WalletPassUpdate;
use App\Services\AppleWalletPushService;
use App\Services\GoogleWalletPassService;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerPointsInboundSyncService;
use App\Util\AdminContext;
use App\Util\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\Support\GrantsMemberPoints;
use Tests\TestCase;

/**
 * スマレジ Webhook 由来のポイント同期後に、Apple / Google ウォレット同期が走ることを検証する。
 */
class SmaregiWebhookWalletSyncTest extends TestCase
{
    use GrantsMemberPoints;
    use RefreshDatabase;

    private const CONTRACT_ID = 'test_contract_wallet_sync';

    protected function setUp(): void
    {
        parent::setUp();
        AdminContext::setCurrentProject(null);
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
            'smaregi.webhook_secret' => '',
            'wallet_pass.enabled' => true,
            'wallet_pass.type_id' => 'pass.IDNect',
            'wallet_pass.web_service_url' => 'https://example.test/api/apple-wallet',
            'google_wallet.enabled' => true,
            'google_wallet.issuer_id' => '3388000000022908862',
        ]);
    }

    public function test_スマレジ同期でポイント差分があるときウォレット同期が呼ばれること(): void
    {
        $member = $this->createLinkedMember(initialPoints: 40, smaregiCustomerId: '42');

        $this->fakeSmaregiHttpSingleCustomer([
            'customerId' => '42',
            'customerCode' => 'M001',
            'point' => '55',
        ]);

        $googleMock = Mockery::mock(GoogleWalletPassService::class);
        $googleMock->shouldReceive('canSyncExistingObjects')->andReturn(true);
        $googleMock->shouldReceive('syncLoyaltyObjectForMember')
            ->once()
            ->with(Mockery::on(fn (Member $m): bool => (int) $m->id === (int) $member->id))
            ->andReturn(true);
        $this->app->instance(GoogleWalletPassService::class, $googleMock);

        $appleMock = Mockery::mock(AppleWalletPushService::class);
        $appleMock->shouldReceive('notifyMemberUpdated')
            ->once()
            ->with(Mockery::on(fn (Member $m): bool => (int) $m->id === (int) $member->id));
        $this->app->instance(AppleWalletPushService::class, $appleMock);

        app(SmaregiCustomerPointsInboundSyncService::class)
            ->syncByCustomerIds(self::CONTRACT_ID, ['42'], 'edited');

        $member->refresh();
        $this->assertSame(55, $member->points);

        $this->assertDatabaseHas('wallet_pass_updates', [
            'member_id' => $member->id,
            'pass_type_identifier' => 'pass.IDNect',
            'serial_number' => 'member-'.$member->id,
        ]);
    }

    public function test_ポイント差分がないときウォレット同期は呼ばれないこと(): void
    {
        $member = $this->createLinkedMember(initialPoints: 40, smaregiCustomerId: '42');
        $member->refresh();
        $this->assertSame(40, $member->points);

        $this->fakeSmaregiHttpSingleCustomer([
            'customerId' => '42',
            'point' => '40',
        ]);

        $googleMock = Mockery::mock(GoogleWalletPassService::class);
        $googleMock->shouldReceive('canSyncExistingObjects')->andReturn(true);
        $googleMock->shouldNotReceive('syncLoyaltyObjectForMember');
        $this->app->instance(GoogleWalletPassService::class, $googleMock);

        $appleMock = Mockery::mock(AppleWalletPushService::class);
        $appleMock->shouldNotReceive('notifyMemberUpdated');
        $this->app->instance(AppleWalletPushService::class, $appleMock);

        WalletPassUpdate::query()->where('member_id', $member->id)->delete();

        app(SmaregiCustomerPointsInboundSyncService::class)
            ->syncByCustomerIds(self::CONTRACT_ID, ['42'], 'edited');

        $member->refresh();
        $this->assertSame(40, $member->points);
        $this->assertDatabaseMissing('wallet_pass_updates', [
            'member_id' => $member->id,
            'pass_type_identifier' => 'pass.IDNect',
        ]);
    }

    public function test_webhook_pos_customers受信後のジョブでウォレット同期が走ること(): void
    {
        $member = $this->createLinkedMember(initialPoints: 10, smaregiCustomerId: '77');

        $this->fakeSmaregiHttpSingleCustomer([
            'customerId' => '77',
            'point' => '25',
        ]);

        $googleMock = Mockery::mock(GoogleWalletPassService::class);
        $googleMock->shouldReceive('canSyncExistingObjects')->andReturn(true);
        $googleMock->shouldReceive('syncLoyaltyObjectForMember')
            ->once()
            ->with(Mockery::on(fn (Member $m): bool => (int) $m->id === (int) $member->id))
            ->andReturn(true);
        $this->app->instance(GoogleWalletPassService::class, $googleMock);

        $appleMock = Mockery::mock(AppleWalletPushService::class);
        $appleMock->shouldReceive('notifyMemberUpdated')
            ->once()
            ->with(Mockery::on(fn (Member $m): bool => (int) $m->id === (int) $member->id));
        $this->app->instance(AppleWalletPushService::class, $appleMock);

        $this->postJson('/api/smaregi/webhook', [
            'contractId' => self::CONTRACT_ID,
            'event' => 'pos:customers',
            'action' => 'edited',
            'customerIds' => ['77'],
        ], [
            'smaregi-event' => 'pos:customers',
            'smaregi-contract-id' => self::CONTRACT_ID,
            'Content-Type' => 'application/json',
        ])->assertStatus(200);

        $job = new SmaregiSyncInboundCustomerPointsJob(self::CONTRACT_ID, ['77'], 'edited');
        $job->handle(
            app(SmaregiCustomerPointsInboundSyncService::class),
            app(SmaregiApiService::class),
        );

        $member->refresh();
        $this->assertSame(25, $member->points);
        $this->assertDatabaseHas('wallet_pass_updates', [
            'member_id' => $member->id,
            'pass_type_identifier' => 'pass.IDNect',
        ]);
    }

    public function test_inline_webhook_updates_contract_members_and_leaves_other_contract_unchanged(): void
    {
        config(['wallet_pass.enabled' => false, 'google_wallet.enabled' => false, 'smaregi.webhook_run_sync_inline' => true]);
        $first = $this->createLinkedMember(10, '77');
        $second = $this->createLinkedMember(11, '77');
        $other = $this->createLinkedMember(12, '77');
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', 'other-contract', 'string', (int) $other->project_id);
        $this->fakeSmaregiHttpSingleCustomer(['customerId' => '77', 'point' => '25']);
        $this->postJson('/api/smaregi/webhook', [
            'contractId' => self::CONTRACT_ID,
            'event' => 'pos:customers',
            'action' => 'edited',
            'customerIds' => ['77'],
        ], ['smaregi-event' => 'pos:customers', 'smaregi-contract-id' => self::CONTRACT_ID])->assertOk();
        $this->assertSame(25, $first->fresh()->points);
        $this->assertSame(25, $second->fresh()->points);
        $this->assertSame(12, $other->fresh()->points);
    }

    public function test_inline_webhook_skips_deleted_project_and_continues_active_project(): void
    {
        config(['wallet_pass.enabled' => false, 'google_wallet.enabled' => false, 'smaregi.webhook_run_sync_inline' => true]);
        $deleted = $this->createLinkedMember(10, '77');
        $active = $this->createLinkedMember(11, '77');
        $deleted->project->delete();
        $this->fakeSmaregiHttpSingleCustomer(['customerId' => '77', 'point' => '25']);
        $this->postJson('/api/smaregi/webhook', [
            'contractId' => self::CONTRACT_ID,
            'event' => 'pos:customers',
            'action' => 'edited',
            'customerIds' => ['77'],
        ], ['smaregi-event' => 'pos:customers', 'smaregi-contract-id' => self::CONTRACT_ID])->assertOk();
        $this->assertSame(25, $active->fresh()->points);
        $this->assertSame(10, $deleted->fresh()->points);
    }

    public function test_member_fetch_sync_updates_only_requested_member(): void
    {
        $first = $this->createLinkedMember(10, '77');
        $second = $this->createLinkedMember(11, '77');
        $this->fakeSmaregiHttpSingleCustomer(['customerId' => '77', 'point' => '25']);
        AdminContext::setCurrentProject($first->project);
        app(TenantContext::class)->requireProject(true);
        try {
            $this->assertTrue(app(SmaregiCustomerPointsInboundSyncService::class)->syncMemberFromSmaregi($first));
            $this->assertSame(25, $first->fresh()->points);
            $this->assertSame((int) $first->project_id, AdminContext::getCurrentProjectId());
            $this->assertTrue(app(TenantContext::class)->requiresProject());
        } finally {
            AdminContext::setCurrentProject(null);
            app(TenantContext::class)->requireProject(false);
        }
        $this->assertSame(11, $second->fresh()->points);
    }

    public function test_inbound_sync_failure_restores_previous_project_and_requirement(): void
    {
        $this->createLinkedMember(10, '77');
        $previous = Project::factory()->create();
        $this->fakeSmaregiHttpSingleCustomer(['customerId' => '77', 'point' => '25']);
        $repository = Mockery::mock(MemberRepository::class);
        $repository->shouldReceive('syncPointsLedger')->once()->andThrow(new \RuntimeException('Simulated ledger failure'));
        $this->app->instance(MemberRepository::class, $repository);
        AdminContext::setCurrentProject($previous);
        app(TenantContext::class)->requireProject(true);
        try {
            try {
                app(SmaregiCustomerPointsInboundSyncService::class)->syncByCustomerIds(self::CONTRACT_ID, ['77'], 'edited');
                $this->fail('The ledger failure must propagate.');
            } catch (\RuntimeException $e) {
                $this->assertSame('Simulated ledger failure', $e->getMessage());
            }
            $this->assertSame((int) $previous->id, AdminContext::getCurrentProjectId());
            $this->assertTrue(app(TenantContext::class)->requiresProject());
        } finally {
            AdminContext::setCurrentProject(null);
            app(TenantContext::class)->requireProject(false);
        }
    }

    private function createLinkedMember(int $initialPoints, string $smaregiCustomerId): Member
    {
        $project = Project::factory()->create();
        app(ShopSettingStore::class)->setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'M001',
            'smaregi_customer_id' => $smaregiCustomerId,
            'points' => 0,
        ]);
        $this->grantMemberPoints($member, $initialPoints, '初期付与');

        return $member->fresh();
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $customerPayload
     */
    private function fakeSmaregiHttpSingleCustomer(array $customerPayload): void
    {
        $idBase = 'https://id.smaregi.dev';
        $apiBase = 'https://api.smaregi.dev';

        Http::fake(function ($request) use ($idBase, $apiBase, $customerPayload) {
            $url = $request->url();
            $method = strtoupper($request->method());

            if (str_contains($url, $idBase) && str_contains($url, '/token')) {
                return Http::response([
                    'access_token' => 'fake_access_token_'.self::CONTRACT_ID,
                    'expires_in' => 3600,
                ], 200);
            }

            if (! str_contains($url, $apiBase) || ! str_contains($url, self::CONTRACT_ID)) {
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
