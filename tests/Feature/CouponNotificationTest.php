<?php

namespace Tests\Feature;

use App\Jobs\SendCouponIssuedNotificationJob;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Models\ShopSetting;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\CouponNotificationService;
use App\Services\Line\LineService;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class CouponNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_member_coupon作成時に通知ジョブがキュー投入されること(): void
    {
        Queue::fake();

        $project = $this->createProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);

        MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'coupon_name_snapshot' => 'テストクーポン',
        ]);

        Queue::assertPushed(SendCouponIssuedNotificationJob::class, function (SendCouponIssuedNotificationJob $job) {
            return $job->memberCouponId > 0;
        });
    }

    public function test_notify_issued_sends_native_push_and_line_message(): void
    {
        $project = $this->createProject();
        $lineUserId = 'U'.str_repeat('a', 32);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId,
        ]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'name' => '春の割引']);
        $memberCoupon = MemberCoupon::withoutEvents(function () use ($project, $member, $coupon) {
            return MemberCoupon::factory()->create([
                'project_id' => $project->id,
                'member_id' => $member->id,
                'coupon_id' => $coupon->id,
                'coupon_name_snapshot' => '春の割引',
            ]);
        });

        UserDevice::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'push_token' => 'onesignal-subscription-id-test',
            'platform' => 'ios',
        ]);

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldReceive('sendToUser')
            ->once()
            ->with(
                $project->id,
                $user->id,
                'クーポンが届きました',
                Mockery::type('string'),
                Mockery::on(fn (array $data) => ($data['type'] ?? '') === 'coupon_issued'
                    && ($data['tab'] ?? '') === 'coupons'
                    && (string) ($data['member_coupon_id'] ?? '') === (string) $memberCoupon->id)
            )
            ->andReturn(['target_tokens' => 1, 'sent' => 1, 'failed' => 0, 'skipped' => 0]);

        $lineMock = Mockery::mock(LineService::class);
        $lineMock->shouldReceive('pushMessage')
            ->once()
            ->with($lineUserId, Mockery::on(fn ($message) => is_array($message) && ($message['type'] ?? '') === 'flex'))
            ->andReturnNull();

        $this->setLineChannelAccessTokenForTest('test-token');

        $service = new CouponNotificationService($pushMock, $lineMock);
        $service->notifyIssued($memberCoupon);

        $this->clearLineChannelAccessTokenForTest();
        $this->addToAssertionCount(2);
    }

    public function test_notify_issued_クーポン機能無効時は通知を送らないこと(): void
    {
        $project = $this->createProject();
        ShopSetting::setValue('feature_coupons_enabled', '0', 'boolean', $project->id);

        $lineUserId = 'U'.str_repeat('b', 32);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId,
        ]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'name' => '無効テスト']);
        $memberCoupon = MemberCoupon::withoutEvents(function () use ($project, $member, $coupon) {
            return MemberCoupon::factory()->create([
                'project_id' => $project->id,
                'member_id' => $member->id,
                'coupon_id' => $coupon->id,
                'coupon_name_snapshot' => '無効テスト',
            ]);
        });

        UserDevice::query()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'push_token' => 'onesignal-subscription-id-test',
            'platform' => 'ios',
        ]);

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldNotReceive('sendToUser');

        $lineMock = Mockery::mock(LineService::class);
        $lineMock->shouldNotReceive('pushMessage');

        $this->setLineChannelAccessTokenForTest('test-token');

        $service = new CouponNotificationService($pushMock, $lineMock);
        $service->notifyIssued($memberCoupon);

        $this->clearLineChannelAccessTokenForTest();
        $this->addToAssertionCount(2);
    }

    private function setLineChannelAccessTokenForTest(string $token): void
    {
        putenv('LINE_CHANNEL_ACCESS_TOKEN='.$token);
        $_ENV['LINE_CHANNEL_ACCESS_TOKEN'] = $token;
        $_SERVER['LINE_CHANNEL_ACCESS_TOKEN'] = $token;
    }

    private function clearLineChannelAccessTokenForTest(): void
    {
        putenv('LINE_CHANNEL_ACCESS_TOKEN');
        unset($_ENV['LINE_CHANNEL_ACCESS_TOKEN'], $_SERVER['LINE_CHANNEL_ACCESS_TOKEN']);
    }
}
