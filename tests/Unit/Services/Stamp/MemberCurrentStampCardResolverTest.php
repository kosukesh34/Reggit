<?php

namespace Tests\Unit\Services\Stamp;

use App\Enums\StampCycleMode;
use App\Models\Member;
use App\Models\MemberStamp;
use App\Models\Project;
use App\Models\StampCard;
use App\Models\User;
use App\Services\Stamp\MemberCurrentStampCardResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberCurrentStampCardResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_カードが無ければnullを返すこと(): void
    {
        $member = $this->createMember();

        $this->assertNull(app(MemberCurrentStampCardResolver::class)->resolve($member));
    }

    public function test_未所持なら有効な開始カードを返すこと(): void
    {
        $member = $this->createMember();
        $card = StampCard::factory()->create([
            'project_id' => $member->project_id,
            'is_active' => true,
        ]);

        $resolved = app(MemberCurrentStampCardResolver::class)->resolve($member);

        $this->assertNotNull($resolved);
        $this->assertSame((int) $card->id, (int) $resolved->id);
    }

    public function test_所持中のカードを優先すること(): void
    {
        $member = $this->createMember();
        StampCard::factory()->create([
            'project_id' => $member->project_id,
            'is_active' => true,
        ]);
        $held = StampCard::factory()->create([
            'project_id' => $member->project_id,
            'is_active' => true,
        ]);
        MemberStamp::factory()->create([
            'project_id' => $member->project_id,
            'member_id' => $member->id,
            'stamp_card_id' => $held->id,
            'current_stamps' => 2,
            'completed_at' => null,
        ]);

        $resolved = app(MemberCurrentStampCardResolver::class)->resolve($member);

        $this->assertNotNull($resolved);
        $this->assertSame((int) $held->id, (int) $resolved->id);
    }

    public function test_達成済みnext_cardなら次のカードを返すこと(): void
    {
        $member = $this->createMember();
        $next = StampCard::factory()->create([
            'project_id' => $member->project_id,
            'is_active' => true,
            'cycle_mode' => StampCycleMode::REPEAT,
        ]);
        $first = StampCard::factory()->create([
            'project_id' => $member->project_id,
            'is_active' => true,
            'cycle_mode' => StampCycleMode::NEXT_CARD,
            'next_stamp_card_id' => $next->id,
        ]);
        MemberStamp::factory()->create([
            'project_id' => $member->project_id,
            'member_id' => $member->id,
            'stamp_card_id' => $first->id,
            'current_stamps' => 10,
            'completed_at' => now(),
        ]);

        $resolved = app(MemberCurrentStampCardResolver::class)->resolve($member);

        $this->assertNotNull($resolved);
        $this->assertSame((int) $next->id, (int) $resolved->id);
    }

    private function createMember(): Member
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);

        return Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
    }
}
