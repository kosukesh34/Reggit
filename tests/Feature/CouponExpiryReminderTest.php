<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Services\Admin\CouponExpiryReminderService;
use App\Services\AnnouncementNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CouponExpiryReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_未使用で期限内のクーポンがある会員に通知すること(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $member = Member::factory()->create(['project_id' => $project->id, 'status' => 'active']);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'name' => '期限近いクーポン']);
        MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'coupon_name_snapshot' => '期限近いクーポン',
            'status' => MemberCoupon::STATUS_UNUSED,
            'valid_until' => now()->addDays(3),
        ]);

        $notifier = Mockery::mock(AnnouncementNotificationService::class);
        $notifier->shouldReceive('send')->once();
        $this->app->instance(AnnouncementNotificationService::class, $notifier);

        $result = app(CouponExpiryReminderService::class)->processReminders($project->id, 7);

        $this->assertSame(1, $result['notified']);
        $this->assertDatabaseHas('announcements', [
            'project_id' => $project->id,
            'title' => 'クーポン期限のお知らせ',
            'delivery_type' => Announcement::DELIVERY_TYPE_PERSONAL,
        ]);
    }

    public function test_使用済みや期限外は通知しないこと(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $member = Member::factory()->create(['project_id' => $project->id, 'status' => 'active']);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_USED,
            'valid_until' => now()->addDays(3),
            'used_at' => now(),
        ]);
        MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_UNUSED,
            'valid_until' => now()->addDays(30),
        ]);

        $notifier = Mockery::mock(AnnouncementNotificationService::class);
        $notifier->shouldReceive('send')->never();
        $this->app->instance(AnnouncementNotificationService::class, $notifier);

        $result = app(CouponExpiryReminderService::class)->processReminders($project->id, 7);

        $this->assertSame(0, $result['notified']);
    }

    public function test_通知済みのクーポンは翌日以降に再通知しないこと(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $member = Member::factory()->create(['project_id' => $project->id, 'status' => 'active']);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_UNUSED,
            'valid_until' => now()->addDays(3),
        ]);

        $notifier = Mockery::mock(AnnouncementNotificationService::class);
        $notifier->shouldReceive('send')->twice();
        $this->app->instance(AnnouncementNotificationService::class, $notifier);
        $service = app(CouponExpiryReminderService::class);

        $this->assertSame(1, $service->processReminders($project->id, 7)['notified']);
        $this->travel(1)->days();
        $this->assertSame(0, $service->processReminders($project->id, 7)['notified']);

        MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_UNUSED,
            'valid_until' => now()->addDays(7),
        ]);
        $this->assertSame(1, $service->processReminders($project->id, 7)['notified']);
    }
}
