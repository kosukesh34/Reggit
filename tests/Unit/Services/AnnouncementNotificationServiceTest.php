<?php

namespace Tests\Unit\Services;

use App\Domain\Settings\Repositories\ShopSettingRepository;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Announcement;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\AnnouncementNotificationService;
use App\Services\Line\LineService;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AnnouncementNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        try {
            Mockery::close();
        } finally {
            parent::tearDown();
        }
    }

    public function test_send_delivers_native_push_and_line_message(): void
    {
        $project = $this->createProject();
        ShopSettingStore::setLineChannelAccessToken('test-token', $project->id);
        $lineUserId = 'U'.str_repeat('a', 32);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId,
        ]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '春のお知らせ',
            'body' => 'キャンペーン開催中です',
            'delivery_type' => Announcement::DELIVERY_TYPE_PERSONAL,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'target_member_ids' => [$member->id],
        ]);

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldReceive('sendAnnouncement')
            ->once()
            ->with(Mockery::on(fn (Announcement $a) => (int) $a->id === (int) $announcement->id))
            ->andReturn([
                'target_tokens' => 1,
                'sent' => 1,
                'failed' => 0,
                'skipped' => 0,
                'notification_ids' => ['onesignal-id'],
            ]);

        $lineMock = Mockery::mock(LineService::class);
        $lineMock->shouldReceive('pushMessage')
            ->once()
            ->with(
                $lineUserId,
                Mockery::on(fn ($message) => is_array($message)
                    && ($message['type'] ?? '') === 'flex'
                    && ($message['altText'] ?? '') === '春のお知らせ'),
                'test-token'
            )
            ->andReturn([]);

        $service = new AnnouncementNotificationService($pushMock, $lineMock, app(ShopSettingRepository::class));
        $result = $service->send($announcement);

        $this->assertSame(1, $result['sent']);
        $this->assertSame(1, $result['line_sent']);
        $this->assertSame(0, $result['line_failed']);
    }

    public function test_send_skips_line_when_member_has_no_line_user_id(): void
    {
        $project = $this->createProject();
        ShopSettingStore::setLineChannelAccessToken('test-token', $project->id);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'guest-no-line',
        ]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => 'アプリのみ',
            'delivery_type' => Announcement::DELIVERY_TYPE_PERSONAL,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'target_member_ids' => [$member->id],
        ]);

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldReceive('sendAnnouncement')
            ->once()
            ->andReturn([
                'target_tokens' => 1,
                'sent' => 1,
                'failed' => 0,
                'skipped' => 0,
                'notification_ids' => [],
            ]);

        $lineMock = Mockery::mock(LineService::class);
        $lineMock->shouldNotReceive('pushMessage');

        $service = new AnnouncementNotificationService($pushMock, $lineMock, app(ShopSettingRepository::class));
        $result = $service->send($announcement);

        $this->assertSame(0, $result['line_sent']);
        $this->assertSame(0, $result['line_failed']);
    }

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }
}
