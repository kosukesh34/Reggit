<?php

namespace Tests\Unit\Services;

use App\Models\Announcement;
use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\CancelledAnnouncementIdStore;
use App\Services\PushNotificationService;
use App\Util\AdminContext;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class PushNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        AdminContext::setCurrentProject(null);

        config([
            'push.onesignal.enabled' => true,
            'push.onesignal.app_id' => 'test-app-id',
            'push.onesignal.api_key' => 'test-api-key',
            'push.onesignal.api_url' => 'https://api.onesignal.com/notifications',
        ]);
    }

    public function test_send_queue_called_to_user_includes_collapse_id(): void
    {
        [$project, $user] = $this->createProjectAndUser();
        $this->createUserDevice($project, $user, 'subscription-id-queue');

        $payload = $this->captureOneSignalPayload(function (PushNotificationService $service) use ($project, $user) {
            $service->sendQueueCalledToUser((int) $project->id, (int) $user->id, '42');
        });

        $this->assertSame('reggit:queue:'.$project->id.':'.$user->id, $payload['collapse_id']);
        $this->assertSame('queue_called', $payload['data']['type']);
    }

    public function test_send_to_user_includes_collapse_id_for_coupon_issued(): void
    {
        [$project, $user] = $this->createProjectAndUser();
        $this->createUserDevice($project, $user, 'subscription-id-coupon');

        $payload = $this->captureOneSignalPayload(function (PushNotificationService $service) use ($project, $user) {
            $service->sendToUser((int) $project->id, (int) $user->id, 'クーポンが届きました', '本文', [
                'type' => 'coupon_issued',
                'tab' => 'coupons',
            ]);
        });

        $this->assertSame('reggit:coupon:'.$project->id.':'.$user->id, $payload['collapse_id']);
    }

    public function test_send_to_user_omits_collapse_id_when_type_is_unknown(): void
    {
        [$project, $user] = $this->createProjectAndUser();
        $this->createUserDevice($project, $user, 'subscription-id-generic');

        $payload = $this->captureOneSignalPayload(function (PushNotificationService $service) use ($project, $user) {
            $service->sendToUser((int) $project->id, (int) $user->id, 'タイトル', '本文', ['type' => 'custom_event']);
        });

        $this->assertArrayNotHasKey('collapse_id', $payload);
    }

    public function test_send_announcement_persists_onesignal_notification_id(): void
    {
        [$project, $user] = $this->createProjectAndUser();
        $this->createUserDevice($project, $user, 'subscription-id-announcement');
        AdminContext::setCurrentProject($project);

        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '配信テスト',
            'body' => '本文',
            'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
        ]);

        /** @var Client&MockObject $http */
        $http = $this->createMock(Client::class);
        $http->expects($this->once())
            ->method('post')
            ->willReturn(new Response(200, [], json_encode([
                'id' => 'onesignal-notification-uuid-1',
                'recipients' => 1,
            ])));

        $service = new PushNotificationService($http);
        $result = $service->sendAnnouncement($announcement);

        $this->assertSame(['onesignal-notification-uuid-1'], $result['notification_ids']);
        $announcement->refresh();
        $this->assertSame(['onesignal-notification-uuid-1'], $announcement->onesignal_notification_ids);
    }

    public function test_cancel_announcement_push_deletes_and_sends_collapse_cancel(): void
    {
        [$project, $user] = $this->createProjectAndUser();
        $this->createUserDevice($project, $user, 'subscription-id-cancel');
        AdminContext::setCurrentProject($project);

        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '取消対象',
            'body' => '本文',
            'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'onesignal_notification_ids' => ['stored-notification-id'],
        ]);

        $deleteCalled = false;
        $cancelPayload = null;

        /** @var Client&MockObject $http */
        $http = $this->createMock(Client::class);
        $http->expects($this->once())
            ->method('delete')
            ->with(
                'https://api.onesignal.com/notifications/stored-notification-id?app_id=test-app-id',
                $this->callback(function (array $options): bool {
                    return ($options['headers']['Authorization'] ?? null) === 'Key test-api-key';
                })
            )
            ->willReturnCallback(function () use (&$deleteCalled) {
                $deleteCalled = true;

                return new Response(200, [], json_encode(['success' => true]));
            });
        $http->expects($this->once())
            ->method('post')
            ->with(
                'https://api.onesignal.com/notifications',
                $this->callback(function (array $options) use (&$cancelPayload): bool {
                    $cancelPayload = $options['json'] ?? null;

                    return is_array($cancelPayload);
                })
            )
            ->willReturn(new Response(200, [], json_encode(['id' => 'cancel-push-id', 'recipients' => 1])));

        $service = new PushNotificationService($http);
        $service->cancelAnnouncementPush($announcement);

        $this->assertTrue($deleteCalled);
        $this->assertIsArray($cancelPayload);
        $this->assertSame('reggit:announcement:'.$announcement->id, $cancelPayload['collapse_id']);
        $this->assertSame('reggit:announcement:'.$announcement->id, $cancelPayload['android_group']);
        $this->assertSame('reggit:announcement:'.$announcement->id, $cancelPayload['thread_id']);
        $this->assertSame(PushNotificationService::DATA_TYPE_ANNOUNCEMENT_CANCELLED, $cancelPayload['data']['type']);
        $this->assertSame((string) $announcement->id, $cancelPayload['data']['announcement_id']);
        $this->assertSame('取消対象', $cancelPayload['data']['original_title']);
        $this->assertSame(['en' => '取消対象'], $cancelPayload['headings']);
        $this->assertSame(['en' => 'このお知らせは取り消されました'], $cancelPayload['contents']);
        $this->assertTrue($cancelPayload['mutable_content']);
        $this->assertTrue($cancelPayload['content_available']);
        $this->assertSame(10, $cancelPayload['priority']);
        $this->assertSame(
            [(int) $announcement->id],
            app(CancelledAnnouncementIdStore::class)->list((int) $project->id)
        );
    }

    public function test_cancel_announcement_push_stores_cancelled_id_even_when_onesignal_disabled(): void
    {
        config(['push.onesignal.enabled' => false]);
        [$project] = $this->createProjectAndUser();
        AdminContext::setCurrentProject($project);

        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '取消対象',
            'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
        ]);

        /** @var Client&MockObject $http */
        $http = $this->createMock(Client::class);
        $http->expects($this->never())->method('post');
        $http->expects($this->never())->method('delete');

        $service = new PushNotificationService($http);
        $service->cancelAnnouncementPush($announcement);

        $this->assertSame(
            [(int) $announcement->id],
            app(CancelledAnnouncementIdStore::class)->list((int) $project->id)
        );
    }

    /**
     * @param  callable(PushNotificationService): void  $callback
     * @return array<string, mixed>
     */
    private function captureOneSignalPayload(callable $callback): array
    {
        $capturedPayload = null;

        /** @var Client&MockObject $http */
        $http = $this->createMock(Client::class);
        $http->expects($this->once())
            ->method('post')
            ->with(
                'https://api.onesignal.com/notifications',
                $this->callback(function (array $options) use (&$capturedPayload): bool {
                    $capturedPayload = $options['json'] ?? null;

                    return is_array($capturedPayload);
                })
            )
            ->willReturn(new Response(200, [], json_encode(['recipients' => 1])));

        $callback(new PushNotificationService($http));

        $this->assertIsArray($capturedPayload);

        return $capturedPayload;
    }

    private function createUserDevice(Project $project, User $user, string $pushToken): void
    {
        AdminContext::setCurrentProject($project);

        UserDevice::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'push_token' => $pushToken,
            'platform' => 'ios',
        ]);
    }

    /** @return array{0: Project, 1: User} */
    private function createProjectAndUser(): array
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => $project->id]);

        return [$project, $user];
    }
}
