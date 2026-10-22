<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Services\Admin\PointExpiryReminderService;
use App\Services\AnnouncementNotificationService;
use App\Services\MemberPointsExpiryCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PointExpiryReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_同じ失効日は通知期間内に一度だけ通知し失効日が変われば再通知すること(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        Member::factory()->create(['project_id' => $project->id, 'status' => 'active', 'points' => 100]);

        $expiresAt = now()->addDays(3)->toDateString();
        $calculator = Mockery::mock(MemberPointsExpiryCalculator::class);
        $calculator->shouldReceive('getExpiryInfo')->andReturnUsing(function () use (&$expiresAt): array {
            return ['expires_at' => $expiresAt, 'expires_at_label' => $expiresAt, 'points_expiring' => 100];
        });
        $this->app->instance(MemberPointsExpiryCalculator::class, $calculator);
        $notifier = Mockery::mock(AnnouncementNotificationService::class);
        $notifier->shouldReceive('send')->twice();
        $this->app->instance(AnnouncementNotificationService::class, $notifier);
        $service = app(PointExpiryReminderService::class);

        $this->assertSame(1, $service->processReminders($project->id, 7)['notified']);
        $this->travel(1)->days();
        $this->assertSame(0, $service->processReminders($project->id, 7)['notified']);

        // 新しく付与されたポイントで失効日が先に延びたら、次の期間で改めて通知する
        $expiresAt = now()->addDays(20)->toDateString();
        $this->travel(14)->days();
        $this->assertSame(1, $service->processReminders($project->id, 7)['notified']);
    }
}
