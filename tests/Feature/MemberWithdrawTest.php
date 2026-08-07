<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MemberWithdrawTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000002';

    private const CONTRACT_ID = 'test_contract_withdraw';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_withdraw_スマレジ会員が既に無い404でも退会できること(): void
    {
        $project = $this->createProject();
        ShopSettingStore::setValue('smaregi_contract_id', self::CONTRACT_ID, 'string', (int) $project->id);
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
            'smaregi.contract_id' => '',
        ]);

        $user = User::factory()->create([
            'line_user_id' => self::LINE_USER_ID,
            'project_id' => $project->id,
        ]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => '132',
        ]);

        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'id.smaregi.dev') && str_contains($url, '/token')) {
                return Http::response([
                    'access_token' => 'fake_access_token',
                    'expires_in' => 3600,
                ], 200);
            }
            if (strtoupper($request->method()) === 'DELETE'
                && str_contains($url, '/pos/customers/132')) {
                return Http::response([
                    'type' => 'about:blank',
                    'title' => 'NotFound',
                    'detail' => '指定されたIDのリソースが見つかりません',
                    'status' => 404,
                ], 404);
            }

            return Http::response([], 500);
        });

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/withdraw?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue($member->fresh()->trashed());
        $this->assertTrue($user->fresh()->trashed());
        $this->assertSame('withdrawn-'.$user->id, $user->fresh()->line_user_id);
    }

    public function test_withdraw_スマレジ未連携ならローカル退会のみで完了すること(): void
    {
        $project = $this->createProject();
        config(['smaregi.contract_id' => '']);

        $user = User::factory()->create([
            'line_user_id' => self::LINE_USER_ID,
            'project_id' => $project->id,
        ]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => null,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/withdraw?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertTrue($member->fresh()->trashed());
        $this->assertTrue($user->fresh()->trashed());
    }
}
