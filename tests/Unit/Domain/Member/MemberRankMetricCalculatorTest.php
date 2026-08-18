<?php

namespace Tests\Unit\Domain\Member;

use App\Domain\Member\Services\MemberRankMetricCalculator;
use App\Enums\RankAggregationType;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\MemberStamp;
use App\Models\MemberStampTransaction;
use App\Models\Project;
use App\Models\StampCard;
use App\Models\User;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\GrantsMemberPoints;
use Tests\TestCase;

class MemberRankMetricCalculatorTest extends TestCase
{
    use GrantsMemberPoints;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    public function test_period_直近期間の獲得のみを集計すること(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setValue('member_rank_aggregation_type', RankAggregationType::PERIOD, 'string', (int) $project->id);
        ShopSettingStore::setValue('member_rank_period_days', 30, 'integer', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'rank' => 'bronze',
            'points' => 0,
        ]);
        $this->grantMemberPoints($member, 80, '古い付与');
        $tx = $member->pointTransactions()->latest('id')->first();
        $this->assertNotNull($tx);
        $tx->forceFill(['created_at' => now()->subDays(40)])->save();
        $this->grantMemberPoints($member->fresh(), 20, '新しい付与');

        $calculator = app(MemberRankMetricCalculator::class);
        $this->assertSame(20, $calculator->points($member->fresh(), (int) $project->id));
    }

    public function test_total_現在の保有数を使うこと(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setValue('member_rank_aggregation_type', RankAggregationType::TOTAL, 'string', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'points' => 0,
        ]);
        $this->grantMemberPoints($member, 50, '付与');

        $calculator = app(MemberRankMetricCalculator::class);
        $this->assertSame(50, $calculator->points($member->fresh(), (int) $project->id));
    }

    public function test_period_スタンプは期間内の増減合計を使うこと(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setValue('member_rank_aggregation_type', RankAggregationType::PERIOD, 'string', (int) $project->id);
        ShopSettingStore::setValue('member_rank_period_days', 10, 'integer', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
        $card = StampCard::factory()->create(['project_id' => $project->id]);
        MemberStamp::factory()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'stamp_card_id' => $card->id,
            'current_stamps' => 3,
        ]);
        $old = MemberStampTransaction::query()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'stamp_card_id' => $card->id,
            'delta' => 5,
            'balance_after' => 5,
            'description' => '古い',
        ]);
        $old->forceFill(['created_at' => now()->subDays(20)])->save();
        $recent = MemberStampTransaction::query()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'stamp_card_id' => $card->id,
            'delta' => 2,
            'balance_after' => 3,
            'description' => '新しい',
        ]);
        $recent->forceFill(['created_at' => now()->subDays(1)])->save();

        $calculator = app(MemberRankMetricCalculator::class);
        $this->assertSame(2, $calculator->stamps($member, (int) $project->id));
    }

    public function test_total_繰り返すカードは周回分を累計に含めること(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setValue('member_rank_aggregation_type', RankAggregationType::TOTAL, 'string', (int) $project->id);

        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
        $card = StampCard::factory()->create([
            'project_id' => $project->id,
            'required_stamps' => 5,
        ]);
        MemberStamp::factory()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'stamp_card_id' => $card->id,
            'current_stamps' => 2,
            'cycle_count' => 1,
        ]);

        $calculator = app(MemberRankMetricCalculator::class);
        $this->assertSame(7, $calculator->stamps($member, (int) $project->id));
    }
}
