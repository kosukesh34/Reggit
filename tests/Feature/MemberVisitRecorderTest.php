<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Services\MemberVisitRecorder;
use App\Util\AdminContext;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberVisitRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_stale_member_cannot_move_last_visit_backwards(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $member = Member::factory()->create(['project_id' => $project->id, 'last_visited_at' => null]);
        $stale = $member->fresh();
        $recorder = app(MemberVisitRecorder::class);

        $recorder->record($member, Carbon::parse('2026-10-09 12:00:00'), 'manual');
        $recorder->record($stale, Carbon::parse('2026-10-08 12:00:00'), 'manual');

        $this->assertSame('2026-10-09 12:00:00', $member->fresh()->last_visited_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-10-09 12:00:00', $stale->last_visited_at->format('Y-m-d H:i:s'));
        $this->assertDatabaseCount('member_visits', 2);
        $recorder->record($stale, Carbon::parse('2026-10-10 12:00:00'), 'manual');
        $this->assertSame('2026-10-10 12:00:00', $member->fresh()->last_visited_at->format('Y-m-d H:i:s'));
    }

}
