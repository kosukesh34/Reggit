<?php

namespace Tests\Unit\Services;

use App\Enums\MemberNotificationPreference;
use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\CancelledAnnouncementIdStore;
use App\Services\PushNotificationService;
use App\Util\AdminContext;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class PushNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private string $credentialsPath;

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        if (isset($this->credentialsPath) && is_file($this->credentialsPath)) {
            @unlink($this->credentialsPath);
        }
        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        AdminContext::setCurrentProject(null);
        Cache::forget('push.fcm.access_token');

        $this->credentialsPath = tempnam(sys_get_temp_dir(), 'fcmcred');
        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        $this->assertNotFalse($key);
        openssl_pkey_export($key, $pem);
        file_put_contents($this->credentialsPath, json_encode([
            'client_email' => 'fcm-test@reggit-dbb5a.iam.gserviceaccount.com',
            'private_key' => $pem,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ]));

        config([
            'push.fcm.enabled' => true,
            'push.fcm.project_id' => 'reggit-dbb5a',
            'push.fcm.credentials' => $this->credentialsPath,
        ]);
    }

    public function test_send_queue_called_to_user_includes_collapse_id(): void
    {
        [$project, $user] = $this->createProjectAndUser();
        $this->createUserDevice($project, $user, 'fcm-token-queue');

        $payload = $this->captureFcmMessage(function (PushNotificationService $service) use ($project, $user) {
            $service->sendQueueCalledToUser((int) $project->id, (int) $user->id, '42');
        });

        $this->assertSame('reggit:queue:'.$project->id.':'.$user->id, $payload['android']['collapse_key'] ?? null);
        $this->assertSame('queue_called', $payload['data']['type']);
    }

    public function test_send_to_user_includes_collapse_id_for_coupon_issued(): void
    {
        [$project, $user] = $this->createProjectAndUser();
        $this->createUserDevice($project, $user, 'fcm-token-coupon');

        $payload = $this->captureFcmMessage(function (PushNotificationService $service) use ($project, $user) {
            $service->sendToUser((int) $project->id, (int) $user->id, 'クーポンが届きました', '本文', [
                'type' => 'coupon_issued',
                'tab' => 'coupons',
            ]);
        });

        $this->assertSame('reggit:coupon:'.$project->id.':'.$user->id, $payload['android']['collapse_key'] ?? null);
    }

    public function test_send_to_user_omits_collapse_id_when_type_is_unknown(): void
    {
        [$project, $user] = $this->createProjectAndUser();
        $this->createUserDevice($project, $user, 'fcm-token-generic');

        $payload = $this->captureFcmMessage(function (PushNotificationService $service) use ($project, $user) {
            $service->sendToUser((int) $project->id, (int) $user->id, 'タイトル', '本文', ['type' => 'custom_event']);
        });

        $this->assertArrayNotHasKey('collapse_key', $payload['android'] ?? []);
    }

    public function test_send_announcement_via_fcm_counts_sent(): void
    {
        [$project, $user] = $this->createProjectAndUser();
        $this->createUserDevice($project, $user, 'fcm-token-announcement');
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        AdminContext::setCurrentProject($project);

        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '配信テスト',
            'body' => '本文',
            'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
        ]);

        $http = $this->mockFcmHttpClient();
        $result = $this->createService($http)->sendAnnouncement($announcement);

        $this->assertSame(1, $result['target_tokens']);
        $this->assertSame(1, $result['sent']);
        $this->assertSame([], $result['notification_ids']);
    }

    public function test_send_announcement_filters_push_tokens_by_notification_preference(): void
    {
        [$project, $couponOnlyUser] = $this->createProjectAndUser();
        $noneUser = User::factory()->create(['project_id' => $project->id]);
        $this->createUserDevice($project, $couponOnlyUser, 'fcm-token-coupon-only');
        $this->createUserDevice($project, $noneUser, 'fcm-token-none');
        $couponOnlyMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $couponOnlyUser->id,
            'notification_preference' => MemberNotificationPreference::COUPON_ONLY,
        ]);
        $noneMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $noneUser->id,
            'notification_preference' => MemberNotificationPreference::NONE,
        ]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'delivery_coupon_id' => $coupon->id,
            'target_member_ids' => [$couponOnlyMember->id, $noneMember->id],
        ]);

        $capturedToken = null;
        $http = $this->mockFcmHttpClient(function (array $message) use (&$capturedToken): void {
            $capturedToken = $message['token'] ?? null;
        });

        $result = $this->createService($http)->sendAnnouncement($announcement);

        $this->assertSame(1, $result['target_tokens']);
        $this->assertSame('fcm-token-coupon-only', $capturedToken);
    }

    public function test_cancel_announcement_push_sends_fcm_collapse_cancel(): void
    {
        [$project, $user] = $this->createProjectAndUser();
        $this->createUserDevice($project, $user, 'fcm-token-cancel');
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        AdminContext::setCurrentProject($project);

        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '取消対象',
            'body' => '本文',
            'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
        ]);

        $cancelPayload = null;
        $http = $this->mockFcmHttpClient(function (array $message) use (&$cancelPayload): void {
            $cancelPayload = $message;
        });

        $this->createService($http)->cancelAnnouncementPush($announcement);

        $this->assertIsArray($cancelPayload);
        $this->assertSame('reggit:announcement:'.$announcement->id, $cancelPayload['android']['collapse_key'] ?? null);
        $this->assertSame(
            PushNotificationService::DATA_TYPE_ANNOUNCEMENT_CANCELLED,
            $cancelPayload['data']['type'] ?? null
        );
        $this->assertSame(
            [(int) $announcement->id],
            app(CancelledAnnouncementIdStore::class)->list((int) $project->id)
        );
    }

    public function test_cancel_announcement_push_stores_cancelled_id_even_when_providers_disabled(): void
    {
        config([
            'push.fcm.enabled' => false,
        ]);
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

        $this->createService($http)->cancelAnnouncementPush($announcement);

        $this->assertSame(
            [(int) $announcement->id],
            app(CancelledAnnouncementIdStore::class)->list((int) $project->id)
        );
    }

    public function test_send_to_user_counts_failed_when_fcm_rejects_token(): void
    {
        [$project, $user] = $this->createProjectAndUser();
        $this->createUserDevice($project, $user, 'fcm-token-bad');

        /** @var Client&MockObject $http */
        $http = $this->createMock(Client::class);
        $http->expects($this->exactly(2))
            ->method('post')
            ->willReturnOnConsecutiveCalls(
                new Response(200, [], json_encode(['access_token' => 'test-access', 'expires_in' => 3600])),
                new Response(404, [], json_encode([
                    'error' => [
                        'status' => 'NOT_FOUND',
                        'message' => 'Requested entity was not found.',
                        'details' => [['errorCode' => 'UNREGISTERED']],
                    ],
                ]))
            );

        $result = $this->createService($http)->sendQueueCalledToUser((int) $project->id, (int) $user->id, '8');

        $this->assertSame(1, $result['target_tokens']);
        $this->assertSame(0, $result['sent']);
        $this->assertSame(1, $result['failed']);
        $this->assertNull(
            UserDevice::query()->where('user_id', $user->id)->value('push_token')
        );
    }

    /**
     * @param  callable(PushNotificationService): void  $callback
     * @return array<string, mixed>
     */
    private function captureFcmMessage(callable $callback): array
    {
        $captured = null;
        $http = $this->mockFcmHttpClient(function (array $message) use (&$captured): void {
            $captured = $message;
        });
        $callback($this->createService($http));
        $this->assertIsArray($captured);

        return $captured;
    }

    /**
     * @param  (callable(array<string, mixed>): void)|null  $onMessage
     */
    private function mockFcmHttpClient(?callable $onMessage = null): Client
    {
        /** @var Client&MockObject $http */
        $http = $this->createMock(Client::class);
        $http->expects($this->atLeastOnce())
            ->method('post')
            ->willReturnCallback(function (string $url, array $options = []) use ($onMessage) {
                if (str_contains($url, 'oauth2.googleapis.com/token')) {
                    return new Response(200, [], json_encode([
                        'access_token' => 'test-access-token',
                        'expires_in' => 3600,
                    ]));
                }

                $message = $options['json']['message'] ?? null;
                $this->assertIsArray($message);
                if ($onMessage !== null) {
                    $onMessage($message);
                }

                return new Response(200, [], json_encode([
                    'name' => 'projects/reggit-dbb5a/messages/0:test',
                ]));
            });

        return $http;
    }

    private function createService(Client $http): PushNotificationService
    {
        return new PushNotificationService($http, app(CancelledAnnouncementIdStore::class));
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
