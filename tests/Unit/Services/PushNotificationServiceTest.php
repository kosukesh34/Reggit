<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
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
