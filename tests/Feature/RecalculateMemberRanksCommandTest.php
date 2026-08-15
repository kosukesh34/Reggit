<?php

namespace Tests\Feature;

use App\Enums\MemberStatus;
use App\Enums\RankAggregationType;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\GrantsMemberPoints;
use Tests\TestCase;

class RecalculateMemberRanksCommandTest extends TestCase
{
    use GrantsMemberPoints;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    public function test_期間集計でランクダウン有効なら指標不足の会員を下げること(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setMemberRankSettings([
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 100, 'order' => 2, 'is_active' => true],
        ], (int) $project->id);
        ShopSettingStore::setValue('member_rank_aggregation_type', RankAggregationType::PERIOD, 'string', (int) $project->id);
        ShopSettingStore::setValue('member_rank_period_days', 30, 'integer', (int) $project->id);
        ShopSettingStore::setValue('is_rank_down_enabled', true, 'boolean', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'rank' => 'gold',
            'status' => MemberStatus::ACTIVE,
            'points' => 0,
        ]);
        $this->grantMemberPoints($member, 150, '期限切れ集計の付与');
        $tx = $member->pointTransactions()->latest('id')->first();
        $this->assertNotNull($tx);
        $tx->forceFill(['created_at' => now()->subDays(40)])->save();

        $this->artisan('member-ranks:recalculate', ['--project-id' => $project->id])
            ->assertSuccessful();

        $this->assertSame('bronze', $member->fresh()->rank);
    }

    public function test_期間集計でランクダウン無効なら指標不足でも下げないこと(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setMemberRankSettings([
            ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'order' => 1, 'is_active' => true],
            ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 100, 'order' => 2, 'is_active' => true],
        ], (int) $project->id);
        ShopSettingStore::setValue('member_rank_aggregation_type', RankAggregationType::PERIOD, 'string', (int) $project->id);
        ShopSettingStore::setValue('member_rank_period_days', 30, 'integer', (int) $project->id);
        ShopSettingStore::setValue('is_rank_down_enabled', false, 'boolean', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'rank' => 'gold',
            'status' => MemberStatus::ACTIVE,
            'points' => 0,
        ]);
        $this->grantMemberPoints($member, 150, '期限切れ集計の付与');
        $tx = $member->pointTransactions()->latest('id')->first();
        $this->assertNotNull($tx);
        $tx->forceFill(['created_at' => now()->subDays(40)])->save();

        $this->artisan('member-ranks:recalculate', ['--project-id' => $project->id])
            ->assertSuccessful();

        $this->assertSame('gold', $member->fresh()->rank);
    }
}
