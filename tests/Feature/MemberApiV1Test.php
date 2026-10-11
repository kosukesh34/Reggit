<?php

namespace Tests\Feature;

use App\Enums\MemberNotificationPreference;
use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\User;
use App\Services\MemberService;
use App\Services\Smaregi\SmaregiCustomerPointsInboundSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberApiV1Test extends TestCase
{
    use RefreshDatabase;

    /** 有効な形式（U + 32桁 hex）でないと UserService がユーザーを特定できない */
    private const LINE_USER_ID = 'U00000000000000000000000000000001';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true]
        );
    }

    public function test_register_モバイルアプリから会員登録できること(): void
    {
        $project = $this->createProject();
        config(['smaregi.contract_id' => '']);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeader('X-Line-User-Id', self::LINE_USER_ID)
            ->post('/api/v1/members/register', [
                'name' => 'API会員',
                'phone' => '090-1234-5678',
                'notification_preference' => MemberNotificationPreference::COUPON_ONLY,
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.member.is_member', true);

        $this->assertDatabaseHas('users', [
            'project_id' => $project->id,
            'line_user_id' => self::LINE_USER_ID,
            'name' => 'API会員',
        ]);
        $this->assertDatabaseHas('members', [
            'project_id' => $project->id,
            'notification_preference' => MemberNotificationPreference::COUPON_ONLY,
        ]);
    }

    public function test_register_ライン名だけ送ってもプロフィール名は空のままであること(): void
    {
        $project = $this->createProject();
        config(['smaregi.contract_id' => '']);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeader('X-Line-User-Id', self::LINE_USER_ID)
            ->post('/api/v1/members/register', [
                'line_name' => 'LINE花子',
                'phone' => '090-1234-5678',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.member.is_member', true)
            ->assertJsonMissingPath('data.member.user.line_name')
            ->assertJsonMissingPath('data.member.line_name');

        $this->assertDatabaseHas('users', [
            'project_id' => $project->id,
            'line_user_id' => self::LINE_USER_ID,
            'line_name' => 'LINE花子',
        ]);
        $user = User::query()->where('line_user_id', self::LINE_USER_ID)->first();
        $this->assertNotNull($user);
        $this->assertNull($user->name);
    }

    public function test_register_プロフィール名とライン名を分けて保存すること(): void
    {
        $this->createProject();
        config(['smaregi.contract_id' => '']);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeader('X-Line-User-Id', self::LINE_USER_ID)
            ->post('/api/v1/members/register', [
                'name' => '田中太郎',
                'line_name' => 'LINE花子',
                'phone' => '090-1234-5678',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'line_user_id' => self::LINE_USER_ID,
            'name' => '田中太郎',
            'line_name' => 'LINE花子',
        ]);
    }

    public function test_update_モバイルアプリと管理画面が同じ会員を更新すること(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeader('X-Line-User-Id', self::LINE_USER_ID)
            ->put('/api/v1/members/me', [
                'name' => '更新会員',
                'phone' => '080-1111-2222',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.member.is_member', true);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => '更新会員', 'phone' => '080-1111-2222']);
        $this->assertDatabaseHas('members', ['id' => $member->id]);
    }

    public function test_me_会員データを返すこと(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/v1/members/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.member.is_member', true);
    }

    public function test_get_points_ポイントを返すこと(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id, 'points' => 30]);
        PointTransaction::create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'type' => 'earned',
            'points' => 30,
            'description' => 'test',
        ]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/v1/members/points');

        $response->assertStatus(200)
            ->assertJsonPath('data.points', 30);
    }

    public function test_cached_member_does_not_expose_another_owners_profile(): void
    {
        config(['smaregi.inbound_sync_on_member_fetch' => false]);
        $user = User::factory()->create(['project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $user->load('member');
        $other = User::factory()->create(['project_id' => 1, 'name' => 'Private new owner']);
        $member->update(['user_id' => $other->id]);

        $data = app(MemberService::class)->getMemberData($user, syncFromSmaregi: false);

        $this->assertFalse($data['is_member']);
        $this->assertArrayNotHasKey('member', $data);
        $this->assertSame([], $data['profile']);
    }

    public function test_cached_deleted_member_is_not_returned_as_an_active_member(): void
    {
        config(['smaregi.inbound_sync_on_member_fetch' => false]);
        $user = User::factory()->create(['project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $user->load('member');
        $member->delete();

        $data = app(MemberService::class)->getMemberData($user, syncFromSmaregi: false);

        $this->assertFalse($data['is_member']);
        $this->assertArrayNotHasKey('member', $data);
    }

    public function test_member_transferred_during_fetch_sync_is_not_returned(): void
    {
        $this->assertMemberRemovedDuringSyncIsNotReturned(true);
    }

    public function test_member_deleted_during_fetch_sync_is_not_returned(): void
    {
        $this->assertMemberRemovedDuringSyncIsNotReturned(false);
    }

    private function assertMemberRemovedDuringSyncIsNotReturned(bool $transfer): void
    {
        config(['smaregi.inbound_sync_on_member_fetch' => true, 'smaregi.inbound_sync_on_member_fetch_ttl_seconds' => 0]);
        $user = User::factory()->create(['project_id' => 1]);
        $member = Member::factory()->create([
            'user_id' => $user->id, 'project_id' => 1, 'smaregi_customer_id' => 'existing',
        ]);
        $other = User::factory()->create(['project_id' => 1, 'name' => 'Private new owner']);
        $this->mock(SmaregiCustomerPointsInboundSyncService::class)
            ->shouldReceive('syncMemberFromSmaregi')->once()->andReturnUsing(function () use ($member, $other, $transfer): bool {
                if ($transfer) {
                    $member->update(['user_id' => $other->id]);
                } else {
                    $member->delete();
                }

                return true;
            });

        $data = app(MemberService::class)->getMemberData($user);

        $this->assertFalse($data['is_member']);
        $this->assertArrayNotHasKey('member', $data);
        $this->assertSame([], $data['profile']);
    }
}
