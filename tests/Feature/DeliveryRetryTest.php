<?php

namespace Tests\Feature;

use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Jobs\SendAnnouncementNotificationJob;
use App\Jobs\SendCouponIssuedNotificationJob;
use App\Jobs\SendScheduledAnnouncementJob;
use App\Jobs\SendScheduledCouponDeliveryJob;
use App\Models\AfterRegistrationDelivery;
use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Services\Admin\AnnouncementAdminService;
use App\Services\Admin\CouponDeliveryTargetService;
use App\Services\Admin\MemberBenefitService;
use App\Services\AnnouncementNotificationService;
use App\Services\ScheduledDeliverySendService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class DeliveryRetryTest extends TestCase
{
    use RefreshDatabase;

    private function announcement(array $attributes = []): Announcement
    {
        $project = Project::factory()->create();

        return Announcement::factory()->create(array_merge([
            'project_id' => $project->id,
            'send_notification' => true,
            'notification_pending' => true,
            'notification_retry_at' => now(),
        ], $attributes));
    }

    public function test_notification_failure_remains_pending_and_can_be_retried_without_republishing(): void
    {
        $announcement = $this->announcement([
            'delivery_status' => Announcement::DELIVERY_STATUS_SCHEDULED,
            'notification_pending' => false,
        ]);
        $notifications = Mockery::mock(AnnouncementNotificationService::class);
        $notifications->shouldReceive('send')->once()->andThrow(new \RuntimeException('Provider unavailable'));
        try {
            (new SendScheduledAnnouncementJob($announcement->id))->handle(
                $notifications, app(AnnouncementAdminService::class),
            );
            $this->fail('Notification failure must reach the queue worker.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Provider unavailable', $exception->getMessage());
        }

        $announcement->refresh();
        $this->assertSame(Announcement::DELIVERY_STATUS_SENT, $announcement->delivery_status);
        $this->assertTrue($announcement->notification_pending);
        $publishedAt = $announcement->published_at->toDateTimeString();

        $this->travel(61)->seconds();
        $notifications = Mockery::mock(AnnouncementNotificationService::class);
        $notifications->shouldReceive('send')->once()->andReturn(['failed' => 0, 'line_failed' => 0]);
        $job = new SendAnnouncementNotificationJob($announcement->id);
        $job->handle($notifications);
        $job->handle($notifications);

        $announcement->refresh();
        $this->assertFalse($announcement->notification_pending);
        $this->assertNull($announcement->notification_retry_at);
        $this->assertSame($publishedAt, $announcement->published_at->toDateTimeString());
    }

    public function test_partial_provider_failure_does_not_complete_notification(): void
    {
        $announcement = $this->announcement();
        $notifications = Mockery::mock(AnnouncementNotificationService::class);
        $notifications->shouldReceive('send')->once()->andReturn(['failed' => 0, 'line_failed' => 1]);
        try {
            (new SendAnnouncementNotificationJob($announcement->id))->handle($notifications);
            $this->fail('Partial delivery must remain retryable.');
        } catch (\RuntimeException) {
            $this->assertTrue($announcement->fresh()->notification_pending);
        }
    }

    public function test_after_registration_delivery_recovers_notification_without_reissuing_coupon(): void
    {
        Queue::fake();
        $project = Project::factory()->create();
        $member = Member::factory()->create(['project_id' => $project->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $delivery = AfterRegistrationDelivery::create([
            'project_id' => $project->id,
            'title' => 'Welcome',
            'coupon_id' => $coupon->id,
            'is_active' => true,
        ]);
        $this->mock(AnnouncementNotificationService::class, function ($mock): void {
            $mock->shouldReceive('send')->once()->andThrow(new \RuntimeException('Provider unavailable'));
        });
        $service = app(ScheduledDeliverySendService::class);
        $this->assertTrue($service->sendToMember($delivery, $member));
        $announcement = Announcement::where('project_id', $project->id)->sole();
        $this->assertTrue($announcement->notification_pending);
        $this->assertFalse($service->sendToMember($delivery, $member));

        $this->travel(61)->seconds();
        $this->mock(AnnouncementNotificationService::class, function ($mock): void {
            $mock->shouldReceive('send')->once()->andReturn(['failed' => 0, 'line_failed' => 0]);
        });
        (new SendAnnouncementNotificationJob($announcement->id))->handle(app(AnnouncementNotificationService::class));

        $this->assertFalse($announcement->fresh()->notification_pending);
        $this->assertSame(1, MemberCoupon::where('member_id', $member->id)->where('coupon_id', $coupon->id)->count());
        $this->assertDatabaseCount('scheduled_delivery_sends', 1);
    }

    public function test_active_lease_prevents_duplicate_delivery_and_expired_lease_recovers(): void
    {
        $announcement = $this->announcement(['notification_retry_at' => now()->addMinutes(5)]);
        $notifications = Mockery::mock(AnnouncementNotificationService::class);
        $notifications->shouldReceive('send')->once()->andReturn(['failed' => 0, 'line_failed' => 0]);
        $job = new SendAnnouncementNotificationJob($announcement->id);
        $job->handle($notifications);
        $this->assertTrue($announcement->fresh()->notification_pending);

        $this->travel(301)->seconds();
        $job->handle($notifications);
        $this->assertFalse($announcement->fresh()->notification_pending);
    }

    public function test_expired_worker_cannot_complete_a_newer_failed_delivery(): void
    {
        $announcement = $this->announcement();
        $replacement = Mockery::mock(AnnouncementNotificationService::class);
        $replacement->shouldReceive('send')->once()->andThrow(new \RuntimeException('Replacement failed'));
        $stale = Mockery::mock(AnnouncementNotificationService::class);
        $stale->shouldReceive('send')->once()->andReturnUsing(function () use ($announcement, $replacement): array {
            $this->travel(301)->seconds();
            try {
                (new SendAnnouncementNotificationJob($announcement->id))->handle($replacement);
            } catch (\RuntimeException $exception) {
                $this->assertSame('Replacement failed', $exception->getMessage());
            }

            return ['failed' => 0, 'line_failed' => 0];
        });
        (new SendAnnouncementNotificationJob($announcement->id))->handle($stale);
        $this->assertTrue($announcement->fresh()->notification_pending);
        $this->assertSame(now()->addMinute()->toDateTimeString(), $announcement->fresh()->notification_retry_at->toDateTimeString());
    }

    public function test_recovery_command_only_dispatches_due_pending_notifications(): void
    {
        Bus::fake();
        $due = $this->announcement();
        $this->announcement(['notification_pending' => false]);
        $this->announcement(['notification_retry_at' => now()->addMinutes(5)]);
        $this->announcement(['send_notification' => false]);
        $this->announcement(['delivery_status' => Announcement::DELIVERY_STATUS_SCHEDULED]);

        $this->artisan('announcements:retry-notifications')->assertSuccessful();

        Bus::assertDispatchedTimes(SendAnnouncementNotificationJob::class, 1);
        Bus::assertDispatched(SendAnnouncementNotificationJob::class, fn ($job) => $job->announcementId === $due->id);
    }

    public function test_notifications_disabled_does_not_create_pending_delivery(): void
    {
        $announcement = $this->announcement([
            'delivery_status' => Announcement::DELIVERY_STATUS_SCHEDULED,
            'send_notification' => false,
            'notification_pending' => false,
        ]);
        $notifications = Mockery::mock(AnnouncementNotificationService::class);
        $notifications->shouldNotReceive('send');
        (new SendScheduledAnnouncementJob($announcement->id))->handle(
            $notifications, app(AnnouncementAdminService::class),
        );

        $this->assertFalse($announcement->fresh()->notification_pending);
        $this->assertNull($announcement->fresh()->notification_retry_at);
    }

    public function test_failed_coupon_batch_rolls_back_issued_coupons_and_notification_jobs(): void
    {
        $project = Project::factory()->create();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id]);
        $delivery = CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::SCHEDULED_PERSONAL,
            'target_member_ids' => [$member->id],
            'status' => CouponDeliveryStatus::SCHEDULED,
            'scheduled_at' => now(),
        ]);
        // Use the real queue and observe dispatches: Queue::fake bypasses
        // the transaction callback that must be discarded on rollback.
        $dispatched = [];
        Queue::before(function ($event) use (&$dispatched): void {
            $dispatched[] = $event->job->resolveName();
        });
        $benefits = Mockery::mock(MemberBenefitService::class);
        $benefits->shouldReceive('issueCouponToMembers')->once()->andReturnUsing(function () use ($member, $coupon) {
            MemberCoupon::create([
                'project_id' => $member->project_id,
                'member_id' => $member->id,
                'coupon_id' => $coupon->id,
                'status' => 'unused',
                'issued_at' => now(),
            ]);
            throw new \RuntimeException('Batch interrupted');
        });

        $job = new SendScheduledCouponDeliveryJob($delivery->id);
        try {
            $job->handle($benefits, app(CouponDeliveryTargetService::class));
            $this->fail('Batch failure must reach the queue worker.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Batch interrupted', $exception->getMessage());
        }

        $this->assertDatabaseMissing('member_coupons', ['member_id' => $member->id, 'coupon_id' => $coupon->id]);
        $this->assertSame(CouponDeliveryStatus::SCHEDULED, $delivery->fresh()->status);
        $this->assertNotContains(SendCouponIssuedNotificationJob::class, $dispatched);

        $job->handle(app(MemberBenefitService::class), app(CouponDeliveryTargetService::class));
        $this->assertSame(CouponDeliveryStatus::SENT, $delivery->fresh()->status);
        $this->assertSame(1, MemberCoupon::where('member_id', $member->id)->where('coupon_id', $coupon->id)->count());
        $this->assertContains(SendCouponIssuedNotificationJob::class, $dispatched);
    }

    public function test_exhausted_coupon_retry_marks_failure_without_changing_successful_delivery(): void
    {
        $project = Project::factory()->create();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $delivery = CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::SCHEDULED_ALL,
            'status' => CouponDeliveryStatus::SCHEDULED,
        ]);
        $job = new SendScheduledCouponDeliveryJob($delivery->id);
        $job->failed(new \RuntimeException('Retries exhausted'));
        $this->assertSame(CouponDeliveryStatus::FAILED, $delivery->fresh()->status);
        $this->assertSame('Retries exhausted', $delivery->fresh()->error_message);

        $delivery->update(['status' => CouponDeliveryStatus::SENT]);
        $job->failed(new \RuntimeException('Late duplicate job failed'));
        $this->assertSame(CouponDeliveryStatus::SENT, $delivery->fresh()->status);
    }
}
