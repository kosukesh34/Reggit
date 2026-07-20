<?php

namespace Tests\Unit\Services;

use App\Models\Member;
use App\Models\MemberRankHistory;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Models\User;
use App\Services\Admin\MemberAdminService;
use App\Services\MemberService;
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
        ShopSetting::setMemberRankSettings([
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
        ShopSetting::setMemberRankSettings([
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
}
