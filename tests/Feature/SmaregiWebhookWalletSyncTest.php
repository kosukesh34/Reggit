<?php

namespace Tests\Feature;

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

    private function createLinkedMember(int $initialPoints, string $smaregiCustomerId): Member
    {
        $project = Project::factory()->create();
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

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
