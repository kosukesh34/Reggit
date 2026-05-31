<?php

namespace Tests\Feature;

use App\Enums\SmaregiEvent;
use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Models\User;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmaregiCustomerPointsWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const CONTRACT_ID = 'test_contract_sb';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
        ]);
    }

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);

        parent::tearDown();
    }

    public function test_webhook_会員ポイント通知でローカル会員のポイントが更新されること(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => '1001',
            'points' => 100,
        ]);
        PointTransaction::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'type' => 'earned',
            'points' => 100,
            'description' => '初期',
        ]);

        $this->fakeSmaregiHttp([
            'get_pos_transactions_2001' => [
                'transactionHeadId' => '2001',
                'transactionHeadDivision' => '6',
                'customerId' => '1001',
                'point' => '100',
                'totalPoint' => '150',
                'cancelDivision' => '0',
            ],
        ]);

        $response = $this->postJson('/api/smaregi/webhook', [
            'contractId' => self::CONTRACT_ID,
            'event' => SmaregiEvent::CUSTOMER_POINTS,
            'action' => 'edited',
            'transactionHeadIds' => ['2001'],
        ], [
            'smaregi-event' => SmaregiEvent::CUSTOMER_POINTS,
            'smaregi-contract-id' => self::CONTRACT_ID,
            'Content-Type' => 'application/json',
        ]);

        $response->assertStatus(200);
        $this->assertSame(150, (int) $member->fresh()->points);
        $this->assertDatabaseHas('point_transactions', [
            'member_id' => $member->id,
            'points' => 50,
            'type' => 'earned',
            'smaregi_transaction_head_id' => '2001',
            'description' => 'スマレジでのポイント加算',
        ]);
    }

    public function test_webhook_同一取引_i_dは二重反映されないこと(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => '1001',
            'points' => 150,
        ]);

        PointTransaction::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'type' => 'earned',
            'points' => 50,
            'description' => '既存',
            'smaregi_transaction_head_id' => '2001',
        ]);

        $this->fakeSmaregiHttp([
            'get_pos_transactions_2001' => [
                'transactionHeadId' => '2001',
                'transactionHeadDivision' => '6',
                'customerId' => '1001',
                'point' => '100',
                'totalPoint' => '150',
                'cancelDivision' => '0',
            ],
        ]);

        $response = $this->postJson('/api/smaregi/webhook', [
            'contractId' => self::CONTRACT_ID,
            'event' => SmaregiEvent::CUSTOMER_POINTS,
            'action' => 'edited',
            'transactionHeadIds' => ['2001'],
        ], [
            'smaregi-event' => SmaregiEvent::CUSTOMER_POINTS,
            'smaregi-contract-id' => self::CONTRACT_ID,
        ]);

        $response->assertStatus(200);
        $this->assertSame(1, PointTransaction::where('smaregi_transaction_head_id', '2001')->count());
        $this->assertSame(150, (int) $member->fresh()->points);
    }

    public function test_webhook_envの契約idでdefaultプロジェクトに反映されること(): void
    {
        config(['smaregi.contract_id' => self::CONTRACT_ID]);

        $project = Project::query()->firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        AdminContext::setCurrentProject($project);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => '1002',
            'points' => 20,
        ]);
        PointTransaction::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'type' => 'earned',
            'points' => 20,
            'description' => '初期',
        ]);

        $this->fakeSmaregiHttp([
            'get_pos_transactions_2002' => [
                'transactionHeadId' => '2002',
                'transactionHeadDivision' => '6',
                'customerId' => '1002',
                'point' => '20',
                'totalPoint' => '25',
                'cancelDivision' => '0',
            ],
        ]);

        $response = $this->postJson('/api/smaregi/webhook', [
            'contractId' => self::CONTRACT_ID,
            'event' => SmaregiEvent::CUSTOMER_POINTS,
            'action' => 'edited',
            'transactionHeadIds' => ['2002'],
        ], [
            'smaregi-event' => SmaregiEvent::CUSTOMER_POINTS,
            'smaregi-contract-id' => self::CONTRACT_ID,
        ]);

        $response->assertStatus(200);
        $this->assertSame(25, (int) $member->fresh()->points);
    }

    /**
     * @param  array<string, array<string, string|int|null>>  $responses
     */
    private function fakeSmaregiHttp(array $responses): void
    {
        $idBase = 'https://id.smaregi.dev';
        $apiBase = 'https://api.smaregi.dev';

        Http::fake(function ($request) use ($idBase, $apiBase, $responses) {
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

            if ($method === 'GET' && preg_match('#/pos/transactions/(\d+)$#', $url, $matches)) {
                $key = 'get_pos_transactions_'.$matches[1];
                if (! isset($responses[$key])) {
                    return Http::response([], 404);
                }

                return Http::response($responses[$key], 200);
            }

            return Http::response([], 404);
        });
    }
}
