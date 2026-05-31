<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminMemberPointSyncTest extends TestCase
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

    public function test_管理画面でポイント加算するとスマレジに同期されること(): void
    {
        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $this->fakeSmaregiHttp([
            'post_pos_customers_point_add' => [
                'customerId' => 'smaregi-1',
                'point' => '50',
            ],
        ]);

        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $user = User::factory()->create(['project_id' => $project->id, 'name' => 'テスト太郎']);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => 'smaregi-1',
            'points' => 100,
            'rank' => 'bronze',
        ]);
        PointTransaction::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'type' => 'earned',
            'points' => 100,
            'description' => '初期',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.members.update', [
                'project_key' => $project->project_key,
                'id' => $member->id,
            ]), [
                'member_number' => $member->member_number,
                'status' => 'active',
                'rank' => $member->rank,
                'point_add' => 50,
                'point_description' => '管理画面テスト',
            ]);

        $response->assertRedirect();
        $this->assertSame(150, (int) $member->fresh()->points);

        Http::assertSent(function ($request) {
            return strtoupper($request->method()) === 'POST'
                && str_contains($request->url(), '/pos/customers/smaregi-1/point/add')
                && ($request->data()['point'] ?? null) === '50';
        });
    }

    public function test_管理画面でポイント減算するとスマレジに同期されること(): void
    {
        $project = Project::factory()->create();
        ShopSetting::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);

        $this->fakeSmaregiHttp([
            'post_pos_customers_point_add' => [
                'customerId' => 'smaregi-2',
                'point' => '-20',
            ],
        ]);

        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $user = User::factory()->create(['project_id' => $project->id, 'name' => 'テスト花子']);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => 'smaregi-2',
            'points' => 100,
            'rank' => 'bronze',
        ]);
        PointTransaction::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'type' => 'earned',
            'points' => 100,
            'description' => '初期',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.members.update', [
                'project_key' => $project->project_key,
                'id' => $member->id,
            ]), [
                'member_number' => $member->member_number,
                'status' => 'active',
                'rank' => $member->rank,
                'point_subtract' => 20,
            ]);

        $response->assertRedirect();
        $this->assertSame(80, (int) $member->fresh()->points);

        Http::assertSent(function ($request) {
            return strtoupper($request->method()) === 'POST'
                && str_contains($request->url(), '/pos/customers/smaregi-2/point/add')
                && ($request->data()['point'] ?? null) === '-20';
        });
    }

    /**
     * @param  array<string, array<int|string, string|int|null>>  $responses
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

            if ($method === 'POST' && str_contains($url, '/point/add')) {
                return Http::response($responses['post_pos_customers_point_add'] ?? ['customerId' => '1', 'point' => '0'], 200);
            }

            if ($method === 'PATCH' && str_contains($url, 'pos/customers/')) {
                return Http::response($responses['patch_pos_customers'] ?? ['customerId' => '1'], 200);
            }

            return Http::response([], 404);
        });
    }
}
