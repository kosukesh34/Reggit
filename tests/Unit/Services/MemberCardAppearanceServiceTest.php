<?php

namespace Tests\Unit\Services;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\MemberCardAppearanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\GrantsMemberPoints;
use Tests\TestCase;

class MemberCardAppearanceServiceTest extends TestCase
{
    use GrantsMemberPoints;
    use RefreshDatabase;

    public function test_ブロンズランクのデフォルト配色がアプリ会員証と一致すること(): void
    {
        $member = $this->createMember('bronze');
        $appearance = app(MemberCardAppearanceService::class)->resolveForMember($member);

        $this->assertSame('bronze', $appearance['rank_key']);
        $this->assertSame('rgb(92, 46, 10)', $appearance['colors']['bg']);
        $this->assertSame('#5c2e0a', $appearance['hex_background']);
    }

    public function test_カスタムcard_colorからグラデーション配色を生成すること(): void
    {
        $member = $this->createMember('gold');
        $appearance = app(MemberCardAppearanceService::class)->resolveForMember($member);

        $this->assertSame('#d97706', $appearance['hex_background']);
        $this->assertCount(4, $appearance['gradient_stops']);
        $this->assertSame('rgb(255, 255, 255)', $appearance['colors']['fg']);
    }

    private function createMember(string $rankKey): Member
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'rank' => $rankKey,
            'points' => 0,
        ]);

        if ($rankKey === 'gold') {
            ShopSettingStore::setMemberRankSettings([
                ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'is_active' => true, 'order' => 1, 'card_color' => '#cd7f32'],
                ['key' => 'gold', 'label' => 'ゴールド', 'min_points' => 1000, 'is_active' => true, 'order' => 2, 'card_color' => '#d97706'],
            ], (int) $project->id);
            $this->grantMemberPoints($member, 1200, 'test');
        } else {
            ShopSettingStore::setMemberRankSettings([
                ['key' => 'bronze', 'label' => 'ブロンズ', 'min_points' => 0, 'is_active' => true, 'order' => 1],
            ], (int) $project->id);
            $this->grantMemberPoints($member, 100, 'test');
        }

        return $member->fresh();
    }
}
