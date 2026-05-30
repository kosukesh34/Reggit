<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Member;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Models\User;
use App\Models\WalletPassRegistration;
use App\Services\AppleWalletPushService;
use App\Services\GoogleWalletPassService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class AdminMembersTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function createAdmin(Project $project): AdminUser
    {
        return AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
    }

    public function test_members_会員一覧にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.members', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_管理画面でポイントを直接指定して更新できること(): void
    {
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
            'wallet_pass.enabled' => true,
            'wallet_pass.type_id' => 'pass.IDNect',
        ]);

        $project = $this->createProject();
        ShopSetting::setValue('smaregi_contract_id', 'test_contract_admin', 'string', (int) $project->id);
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id, 'name' => '大牟田 テスト']);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => '93',
            'member_number' => 'M-ADMIN-1',
            'points' => 0,
        ]);
        $member->addPoints(10, '初期');

        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'id.smaregi.dev') && str_contains($url, '/token')) {
                return Http::response(['access_token' => 'fake', 'expires_in' => 3600], 200);
            }
            if (str_contains($url, 'test_contract_admin') && str_contains($url, '/point/add')) {
                return Http::response(['customerId' => '93', 'point' => '490'], 200);
            }
            if (str_contains($url, 'test_contract_admin') && str_contains($url, '/pos/customers/93')) {
                return Http::response(['customerId' => '93'], 200);
            }

            return Http::response([], 404);
        });

        $googleMock = Mockery::mock(GoogleWalletPassService::class);
        $googleMock->shouldReceive('canSyncExistingObjects')->andReturn(true);
        $googleMock->shouldReceive('syncLoyaltyObjectForMember')->atLeast()->once()->andReturnTrue();
        $this->app->instance(GoogleWalletPassService::class, $googleMock);

        $appleMock = Mockery::mock(AppleWalletPushService::class);
        $appleMock->shouldReceive('notifyMemberUpdated')->once()->andReturnTrue();
        $this->app->instance(AppleWalletPushService::class, $appleMock);

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.members.update', ['project_key' => $project->project_key, 'id' => $member->id]), [
                'name' => '大牟田 テスト',
                'member_number' => 'M-ADMIN-1',
                'status' => 'active',
                'point_set' => 500,
            ]);

        $response->assertRedirect();
        $member->refresh();
        $this->assertSame(500, $member->points);
        $this->assertSame(500, $member->getTotalPointsFromTransactions());
    }

    public function test_取引なしでカラムだけ残っている会員でもポイント直接指定が正しく反映されること(): void
    {
        config([
            'wallet_pass.enabled' => true,
            'wallet_pass.type_id' => 'pass.IDNect',
            'wallet_pass.web_service_url' => 'https://example.test/api/apple-wallet',
            'google_wallet.enabled' => true,
        ]);

        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'member_number' => 'M-ADMIN-LEDGER',
            'points' => 192,
        ]);

        WalletPassRegistration::query()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'pass_type_identifier' => 'pass.IDNect',
            'serial_number' => 'member-'.$member->id,
            'device_library_identifier' => 'device-test-ledger',
            'push_token' => str_repeat('a', 64),
        ]);

        $googleMock = Mockery::mock(GoogleWalletPassService::class);
        $googleMock->shouldReceive('canSyncExistingObjects')->andReturn(true);
        $googleMock->shouldReceive('syncLoyaltyObjectForMember')->atLeast()->once()->andReturnTrue();
        $this->app->instance(GoogleWalletPassService::class, $googleMock);

        $appleMock = Mockery::mock(AppleWalletPushService::class);
        $appleMock->shouldReceive('notifyMemberUpdated')->atLeast()->once()->andReturnTrue();
        $this->app->instance(AppleWalletPushService::class, $appleMock);

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.members.update', ['project_key' => $project->project_key, 'id' => $member->id]), [
                'name' => 'テスト会員',
                'member_number' => 'M-ADMIN-LEDGER',
                'status' => 'active',
                'point_set' => 100,
            ]);

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $member->refresh();
        $this->assertSame(100, $member->points);
        $this->assertSame(100, $member->getTotalPointsFromTransactions());
        $this->assertDatabaseHas('wallet_pass_updates', [
            'member_id' => $member->id,
            'pass_type_identifier' => 'pass.IDNect',
        ]);
    }

    public function test_カラムだけ多いポイント残高は台帳に補完されること(): void
    {
        $member = Member::factory()->create(['points' => 250]);
        $this->assertSame(0, $member->getTotalPointsFromTransactions());

        $member->syncPointsLedger();
        $member->refresh();

        $this->assertSame(250, $member->points);
        $this->assertSame(250, $member->getTotalPointsFromTransactions());
    }

    public function test_不正なメールでも会員プロフィール更新が失敗しないこと(): void
    {
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
        ]);

        $project = $this->createProject();
        ShopSetting::setValue('smaregi_contract_id', 'test_contract_admin', 'string', (int) $project->id);
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id, 'email' => 'old@example.com']);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'smaregi_customer_id' => '91',
            'member_number' => 'M-ADMIN-2',
        ]);

        Http::fake(function ($request) {
            $url = $request->url();
            if (str_contains($url, 'id.smaregi.dev') && str_contains($url, '/token')) {
                return Http::response(['access_token' => 'fake', 'expires_in' => 3600], 200);
            }
            if (str_contains($url, 'test_contract_admin') && str_contains($url, '/pos/customers/91')) {
                return Http::response(['customerId' => '91'], 200);
            }

            return Http::response([], 404);
        });

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.members.update', ['project_key' => $project->project_key, 'id' => $member->id]), [
                'name' => '更新後 名前',
                'email' => '',
                'member_number' => 'M-ADMIN-2',
                'status' => 'active',
            ]);

        $response->assertRedirect();
        $user->refresh();
        $this->assertNull($user->email);
        $this->assertSame('更新後 名前', $user->name);
    }
}
