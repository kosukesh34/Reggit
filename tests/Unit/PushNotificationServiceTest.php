<?php

namespace Tests\Unit;

use App\Models\Announcement;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\PushNotificationService;
use App\Support\PushCollapseIdBuilder;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PushNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function enableOneSignalConfig(): void
    {
        config([
            'push.onesignal.enabled' => true,
            'push.onesignal.app_id' => 'test-app-id',
            'push.onesignal.api_key' => 'test-api-key',
            'push.onesignal.api_url' => 'https://api.onesignal.com/notifications',
        ]);
    }

    public function test_send_to_user_includes_collapse_id_in_onesignal_json(): void
    {
        $this->enableOneSignalConfig();

        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => $project->id]);
        UserDevice::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'push_token' => 'subscription-id-1',
            'platform' => 'ios',
        ]);

        $collapseId = PushCollapseIdBuilder::forCouponIssued($project->id, 123);
        $capturedJson = null;

        $http = Mockery::mock(Client::class);
        $http->shouldReceive('post')
            ->once()
            ->with(
                'https://api.onesignal.com/notifications',
                Mockery::on(function (array $options) use (&$capturedJson, $collapseId) {
                    $capturedJson = $options['json'] ?? null;

                    return is_array($capturedJson)
                        && ($capturedJson['collapse_id'] ?? '') === $collapseId;
                })
            )
            ->andReturn(new Response(200, [], json_encode(['recipients' => 1])));

        $service = new PushNotificationService($http);
        $service->sendToUser(
            $project->id,
            $user->id,
            'テストタイトル',
            'テスト本文',
            ['type' => 'coupon_issued'],
            $collapseId
        );

        $this->assertIsArray($capturedJson);
        $this->assertSame($collapseId, $capturedJson['collapse_id']);
    }

    public function test_cancel_announcement_push_sends_cancel_payload(): void
    {
        $this->enableOneSignalConfig();

        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        UserDevice::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'push_token' => 'subscription-id-cancel',
            'platform' => 'ios',
        ]);

        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
        ]);

        $expectedCollapseId = PushCollapseIdBuilder::forAnnouncement($project->id, $announcement->id);
        $capturedJson = null;

        $http = Mockery::mock(Client::class);
        $http->shouldReceive('post')
            ->once()
            ->with(
                'https://api.onesignal.com/notifications',
                Mockery::on(function (array $options) use (&$capturedJson, $expectedCollapseId) {
                    $capturedJson = $options['json'] ?? null;

                    return is_array($capturedJson)
                        && ($capturedJson['collapse_id'] ?? '') === $expectedCollapseId
                        && ($capturedJson['content_available'] ?? false) === true
                        && ($capturedJson['headings']['en'] ?? null) === ''
                        && ($capturedJson['contents']['en'] ?? null) === ''
                        && ($capturedJson['data']['action'] ?? '') === 'cancel';
                })
            )
            ->andReturn(new Response(200, [], json_encode(['recipients' => 1])));

        $service = new PushNotificationService($http);
        $service->cancelAnnouncementPush($announcement);

        $this->assertIsArray($capturedJson);
        $this->assertSame($expectedCollapseId, $capturedJson['collapse_id']);
        $this->assertTrue($capturedJson['content_available']);
        $this->assertSame('cancel', $capturedJson['data']['action']);
    }

    public function test_cancel_announcement_push_is_no_op_when_onesignal_disabled(): void
    {
        config([
            'push.onesignal.enabled' => false,
            'push.onesignal.app_id' => '',
            'push.onesignal.api_key' => '',
        ]);

        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
        ]);

        $http = Mockery::mock(Client::class);
        $http->shouldNotReceive('post');

        $service = new PushNotificationService($http);
        $result = $service->cancelAnnouncementPush($announcement);

        $this->assertSame(0, $result['target_tokens']);
        $this->assertSame(0, $result['sent']);
    }
}
