<?php

namespace Tests\Unit\Services;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\MemberRankHistory;
use App\Models\Project;
use App\Models\User;
use App\Services\Admin\MemberAdminService;
use App\Services\MemberService;
use App\Services\Smaregi\SmaregiApiService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\GrantsMemberPoints;
use Tests\TestCase;

class MemberRankHistoryTest extends TestCase
{
    use GrantsMemberPoints;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    public function test_sync_member_rankでランク変更時に履歴が記録されること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setMemberRankSettings([
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 100, 'order' => 2, 'is_active' => true],
        ], $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'rank' => 'bronze',
            'points' => 0,
        ]);
        $this->grantMemberPoints($member, 150, 'ランクアップ用');

        $data = app(MemberService::class)->getMemberData($user->fresh(['member']));

        $this->assertTrue($data['is_member']);
        $this->assertDatabaseHas('member_rank_histories', [
            'member_id' => $member->id,
            'project_id' => $project->id,
            'from_rank' => 'bronze',
            'to_rank' => 'gold',
            'from_rank_label' => 'ブロンズ',
            'to_rank_label' => 'ゴールド',
        ]);
        $this->assertNotEmpty($data['rank_up_history']);
        $this->assertSame('ブロンズ', $data['rank_up_history'][0]['from_rank_label']);
        $this->assertSame('ゴールド', $data['rank_up_history'][0]['to_rank_label']);
    }

    public function test_管理画面のランク変更で履歴が記録されること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        ShopSettingStore::setMemberRankSettings([
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true],
            ['key' => 'silver', 'label' => 'シルバー', 'min_points' => 50, 'order' => 2, 'is_active' => true],
        ], $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'rank' => 'bronze',
            'smaregi_customer_id' => null,
        ]);

        app(MemberAdminService::class)->updateMemberProfile($member, [], ['rank' => 'silver']);

        $this->assertDatabaseHas('member_rank_histories', [
            'member_id' => $member->id,
            'from_rank' => 'bronze',
            'to_rank' => 'silver',
            'to_rank_label' => 'シルバー',
        ]);
        $this->assertSame(1, MemberRankHistory::where('member_id', $member->id)->count());
    }

    public function test_管理画面のランク変更でスマレジへrankが送信されること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        ShopSettingStore::setMemberRankSettings([
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true, 'smaregi_rank_id' => '11'],
            ['key' => 'silver', 'label' => 'シルバー', 'min_points' => 50, 'order' => 2, 'is_active' => true, 'smaregi_rank_id' => '22'],
        ], $project->id);

        $user = User::factory()->create(['project_id' => $project->id, 'name' => '山田 太郎']);
        $member = Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'rank' => 'bronze',
            'smaregi_customer_id' => 'cust-200',
        ]);

        $rankSynced = false;
        $smaregiApi = \Mockery::mock(SmaregiApiService::class)->makePartial();
        $smaregiApi->shouldReceive('getContractIdForProject')->andReturn('contract-x');
        $smaregiApi->shouldReceive('clearLastErrorDetail')->andReturnNull();
        $smaregiApi->shouldReceive('updateCustomer')
            ->andReturnUsing(function (string $contractId, string $customerId, array $body) use (&$rankSynced) {
                if ($contractId === 'contract-x' && $customerId === 'cust-200' && ($body['rank'] ?? null) === '22') {
                    $rankSynced = true;
                }

                return ['customerId' => 'cust-200'];
            });
        $this->app->instance(SmaregiApiService::class, $smaregiApi);
        config(['smaregi.client_id' => 'test-client', 'smaregi.client_secret' => 'test-secret']);

        app(MemberAdminService::class)->updateMemberProfile($member, [], ['rank' => 'silver']);

        $this->assertTrue($rankSynced);
        $this->assertSame('silver', $member->fresh()->rank);
    }
}
