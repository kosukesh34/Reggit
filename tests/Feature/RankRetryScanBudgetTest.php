<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Models\SmaregiMemberRankSync;
use App\Models\SmaregiRankSettingsSync;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use App\Services\Smaregi\SmaregiRankSettingsRecoveryService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankRetryScanBudgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_slow_project_leaves_later_projects_and_members_for_next_scan(): void
    {
        $first = Project::factory()->create();
        $second = Project::factory()->create();
        foreach ([$first, $second] as $index => $project) {
            SmaregiRankSettingsSync::create(['project_id' => $project->id, 'request_token' => 'pending', 'retry_at' => now()->subMinutes(2 - $index)]);
        }
        $member = Member::factory()->create(['project_id' => $second->id]);
        SmaregiMemberRankSync::create(['member_id' => $member->id, 'project_id' => $second->id, 'request_token' => 'pending', 'retry_at' => now()]);
        $this->mock(SmaregiRankSettingsRecoveryService::class, function ($mock) use ($first, $second): void {
            $mock->shouldReceive('retryPending')->once()->with($first->id)->andReturnUsing(function () use ($first): bool {
                SmaregiRankSettingsSync::whereKey($first->id)->update(['delivered_at' => now()]);
                $this->travel(46)->seconds();

                return true;
            });
            $mock->shouldReceive('retryPending')->once()->with($second->id)->andReturnUsing(function () use ($second): bool {
                SmaregiRankSettingsSync::whereKey($second->id)->update(['delivered_at' => now()]);

                return true;
            });
        });
        $this->mock(SmaregiCustomerRankAssignmentService::class, function ($mock) use ($member): void {
            $mock->shouldReceive('retryPending')->once()->with($member->id)->andReturnUsing(function () use ($member): bool {
                SmaregiMemberRankSync::whereKey($member->id)->delete();

                return true;
            });
        });

        $this->artisan('smaregi:retry-member-ranks')->assertSuccessful();
        $this->assertNull(SmaregiRankSettingsSync::findOrFail($second->id)->delivered_at);
        $this->assertDatabaseHas('smaregi_member_rank_syncs', ['member_id' => $member->id]);
        $this->artisan('smaregi:retry-member-ranks')->assertSuccessful();
        $this->assertNotNull(SmaregiRankSettingsSync::findOrFail($second->id)->delivered_at);
        $this->assertDatabaseMissing('smaregi_member_rank_syncs', ['member_id' => $member->id]);
    }

    public function test_slow_member_leaves_remaining_work_for_next_scan(): void
    {
        $members = Member::factory()->count(2)->create();
        foreach ($members as $index => $member) {
            SmaregiMemberRankSync::create(['member_id' => $member->id, 'project_id' => $member->project_id, 'request_token' => 'pending', 'retry_at' => now()->subMinutes(2 - $index)]);
        }
        $this->mock(SmaregiCustomerRankAssignmentService::class, function ($mock) use ($members): void {
            foreach ($members as $index => $member) {
                $mock->shouldReceive('retryPending')->once()->with($member->id)->andReturnUsing(function () use ($member, $index): bool {
                    SmaregiMemberRankSync::whereKey($member->id)->delete();
                    if ($index === 0) {
                        $this->travel(46)->seconds();
                    }

                    return true;
                });
            }
        });

        $this->artisan('smaregi:retry-member-ranks')->assertSuccessful();
        $this->assertDatabaseCount('smaregi_member_rank_syncs', 1);
        $this->artisan('smaregi:retry-member-ranks')->assertSuccessful();
        $this->assertDatabaseCount('smaregi_member_rank_syncs', 0);
    }

    public function test_project_exception_does_not_prevent_other_project_or_member_work(): void
    {
        $projects = Project::factory()->count(2)->create();
        foreach ($projects as $index => $project) {
            SmaregiRankSettingsSync::create(['project_id' => $project->id, 'request_token' => 'pending', 'retry_at' => now()->subMinutes(2 - $index)]);
        }
        $member = Member::factory()->create();
        SmaregiMemberRankSync::create(['member_id' => $member->id, 'project_id' => $member->project_id, 'request_token' => 'pending', 'retry_at' => now()]);
        $this->mock(SmaregiRankSettingsRecoveryService::class, function ($mock) use ($projects): void {
            $mock->shouldReceive('retryPending')->once()->with($projects[0]->id)->andThrow(new \RuntimeException('claim failed'));
            $mock->shouldReceive('retryPending')->once()->with($projects[1]->id)->andReturn(true);
        });
        $this->mock(SmaregiCustomerRankAssignmentService::class, function ($mock) use ($member): void {
            $mock->shouldReceive('retryPending')->once()->with($member->id)->andReturn(true);
        });

        $this->artisan('smaregi:retry-member-ranks')->assertFailed();
        $this->assertDatabaseCount('smaregi_rank_settings_syncs', 2);
        $this->assertDatabaseCount('smaregi_member_rank_syncs', 1);
    }

    public function test_rank_scan_does_not_block_other_scheduler_events(): void
    {
        $events = collect(app(Schedule::class)->events());
        $rank = $events->first(fn ($event) => str_contains($event->command ?? '', 'smaregi:retry-member-ranks'));
        $this->assertNotNull($rank);
        $this->assertTrue($rank->runInBackground);
        $this->assertTrue($rank->withoutOverlapping);
        $this->assertSame(5, $rank->expiresAt);
        $this->assertSame('* * * * *', $rank->expression);
    }
}
