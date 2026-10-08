<?php

namespace Tests\Feature;

use App\Enums\ScheduledDeliveryType;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AfterRegistrationDelivery;
use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Project;
use App\Services\AnnouncementNotificationService;
use App\Services\MemberRankCalculator;
use App\Services\MemberRankSyncService;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MemberRankTransactionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate:fresh')->assertExitCode(0);
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_rank_delivery_waits_for_outer_commit_and_rollback_discards_it(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setMemberRankSettings([
            ['key' => 'bronze', 'label' => 'Bronze', 'min_points' => 0, 'order' => 1, 'is_active' => true],
            ['key' => 'gold', 'label' => 'Gold', 'min_points' => 100, 'order' => 2, 'is_active' => true],
        ], $project->id);
        $member = Member::factory()->create(['project_id' => $project->id, 'rank' => 'bronze']);
        $this->mock(MemberRankCalculator::class, function ($mock): void {
            $mock->shouldReceive('getRankInfo')->andReturn(['current_rank' => 'gold', 'current_rank_label' => 'Gold']);
        });
        AfterRegistrationDelivery::create([
            'project_id' => $project->id, 'schedule_type' => ScheduledDeliveryType::RANK_UP_ARRIVAL,
            'params' => ['reach_rank' => 'gold'], 'title' => 'Gold welcome', 'is_active' => true,
        ]);
        $sent = 0;
        $synced = 0;
        $this->mock(AnnouncementNotificationService::class, function ($mock) use (&$sent): void {
            $mock->shouldReceive('send')->andReturnUsing(function () use (&$sent): array {
                $this->assertSame(0, DB::transactionLevel());
                $sent++;

                return ['failed' => 0, 'line_failed' => 0];
            });
        });
        $this->mock(SmaregiCustomerRankAssignmentService::class, function ($mock) use (&$synced): void {
            $mock->shouldReceive('assignRankToCustomer')->andReturnUsing(function () use (&$synced): void {
                $this->assertSame(0, DB::transactionLevel());
                $synced++;
            });
        });
        $service = app(MemberRankSyncService::class);

        DB::beginTransaction();
        $this->assertTrue($service->sync($member));
        $this->assertSame(0, $sent);
        $this->assertSame(0, $synced);
        DB::rollBack();
        $this->assertSame('bronze', $member->fresh()->rank);
        $this->assertDatabaseCount('member_rank_histories', 0);
        $this->assertDatabaseCount('scheduled_delivery_sends', 0);
        $this->assertDatabaseCount('announcements', 0);

        DB::beginTransaction();
        $this->assertTrue($service->sync($member));
        $this->assertSame(0, $sent);
        DB::commit();
        $this->assertSame(1, $sent);
        $this->assertSame(1, $synced);
        $this->assertDatabaseCount('member_rank_histories', 1);
        $this->assertDatabaseCount('scheduled_delivery_sends', 1);
        $this->assertDatabaseHas('announcements', ['notification_pending' => false]);
    }

    public function test_failed_notification_leaves_retry_work_and_does_not_stop_later_rank_deliveries(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $member = Member::factory()->create(['project_id' => $project->id, 'rank' => 'bronze']);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $this->mock(MemberRankCalculator::class, function ($mock): void {
            $mock->shouldReceive('getRankInfo')->andReturn(['current_rank' => 'gold', 'current_rank_label' => 'Gold']);
        });
        foreach (['First', 'Second'] as $title) {
            AfterRegistrationDelivery::create([
                'project_id' => $project->id, 'schedule_type' => ScheduledDeliveryType::RANK_UP_ARRIVAL,
                'params' => ['reach_rank' => 'gold'], 'title' => $title, 'coupon_id' => $coupon->id, 'is_active' => true,
            ]);
        }
        $calls = 0;
        $this->mock(AnnouncementNotificationService::class, function ($mock) use (&$calls): void {
            $mock->shouldReceive('send')->andReturnUsing(function () use (&$calls): array {
                $calls++;
                if ($calls === 1) {
                    throw new \RuntimeException('provider unavailable');
                }

                return ['failed' => 0, 'line_failed' => 0];
            });
        });
        $this->mock(SmaregiCustomerRankAssignmentService::class, function ($mock): void {
            $mock->shouldReceive('assignRankToCustomer')->andReturnNull();
        });
        $service = app(MemberRankSyncService::class);
        $this->assertTrue($service->sync($member));
        $this->assertFalse($service->sync($member));
        $this->assertSame('gold', $member->fresh()->rank);
        $this->assertSame(2, $calls);
        $this->assertDatabaseCount('member_rank_histories', 1);
        $this->assertDatabaseCount('scheduled_delivery_sends', 2);
        $this->assertDatabaseCount('member_coupons', 2);
        $this->assertDatabaseHas('announcements', ['title' => 'First', 'notification_pending' => true]);
        $this->assertNotNull(Announcement::where('title', 'First')->firstOrFail()->notification_retry_at);
        $this->assertDatabaseHas('announcements', ['title' => 'Second', 'notification_pending' => false]);
    }
}
