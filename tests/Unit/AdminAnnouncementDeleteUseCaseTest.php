<?php

namespace Tests\Unit;

use App\Models\Announcement;
use App\Services\Admin\AnnouncementAdminService;
use App\Services\PushNotificationService;
use App\UseCases\Admin\Announcement\AdminAnnouncementDeleteUseCase;
use Mockery;
use Tests\TestCase;

class AdminAnnouncementDeleteUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_handle_sent_announcement_cancels_push_before_delete(): void
    {
        $announcement = new Announcement([
            'id' => 10,
            'project_id' => 1,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
        ]);
        $announcement->id = 10;

        $announcementAdminService = Mockery::mock(AnnouncementAdminService::class);
        $announcementAdminService->shouldReceive('findOrFail')
            ->once()
            ->with(10)
            ->andReturn($announcement);
        $announcementAdminService->shouldReceive('delete')
            ->once()
            ->with(10);

        $pushNotificationService = Mockery::mock(PushNotificationService::class);
        $pushNotificationService->shouldReceive('cancelAnnouncementPush')
            ->once()
            ->with($announcement)
            ->andReturn(['target_tokens' => 1, 'sent' => 1, 'failed' => 0, 'skipped' => 0]);

        $useCase = new AdminAnnouncementDeleteUseCase($announcementAdminService, $pushNotificationService);
        $useCase->handle(10);

        $this->addToAssertionCount(3);
    }

    public function test_handle_draft_announcement_skips_cancel_push(): void
    {
        $announcement = new Announcement([
            'id' => 20,
            'project_id' => 1,
            'delivery_status' => Announcement::DELIVERY_STATUS_DRAFT,
        ]);
        $announcement->id = 20;

        $announcementAdminService = Mockery::mock(AnnouncementAdminService::class);
        $announcementAdminService->shouldReceive('findOrFail')
            ->once()
            ->with(20)
            ->andReturn($announcement);
        $announcementAdminService->shouldReceive('delete')
            ->once()
            ->with(20);

        $pushNotificationService = Mockery::mock(PushNotificationService::class);
        $pushNotificationService->shouldNotReceive('cancelAnnouncementPush');

        $useCase = new AdminAnnouncementDeleteUseCase($announcementAdminService, $pushNotificationService);
        $useCase->handle(20);

        $this->addToAssertionCount(3);
    }
}
