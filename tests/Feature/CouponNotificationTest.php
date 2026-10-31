<?php

namespace Tests\Feature;

use App\Domain\Settings\Repositories\CouponNotificationSettings;
use App\Enums\MemberNotificationPreference;
use App\Infrastructure\Persistence\Eloquent\Settings\LineSettingStore;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Jobs\SendCouponIssuedNotificationJob;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\CouponNotificationService;
use App\Services\Line\LineService;
use App\Services\PushNotificationService;
use App\Support\LiffTabUrlBuilder;
use App\Util\AdminContext;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class CouponNotificationTest extends TestCase
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
        LineSettingStore::setLineChannelAccessToken('test-token', $project->id);
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
            'push_token' => 'fcm-registration-token-test',
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
            ->with(
                $lineUserId,
                Mockery::on(fn ($message) => is_array($message) && ($message['type'] ?? '') === 'flex'),
                'test-token'
            )
            ->andReturn([]);

        $service = new CouponNotificationService($pushMock, $lineMock, app(CouponNotificationSettings::class), app(LiffTabUrlBuilder::class));
        $service->notifyIssued($memberCoupon);

        $this->addToAssertionCount(2);
    }

    public function test_notify_issued_attaches_hero_image_for_https_image_url(): void
    {
        $project = $this->createProject();
        LineSettingStore::setLineChannelAccessToken('test-token', $project->id);
        $lineUserId = 'U'.str_repeat('f', 32);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId,
        ]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $imageUrl = 'https://cdn.example.com/coupon.png';
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => '画像付き',
            'image_url' => $imageUrl,
        ]);
        $memberCoupon = MemberCoupon::withoutEvents(function () use ($project, $member, $coupon) {
            return MemberCoupon::factory()->create([
                'project_id' => $project->id,
                'member_id' => $member->id,
                'coupon_id' => $coupon->id,
                'coupon_name_snapshot' => '画像付き',
            ]);
        });

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldReceive('sendToUser')
            ->once()
            ->andReturn(['target_tokens' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0]);

        $lineMock = Mockery::mock(LineService::class);
        $lineMock->shouldReceive('pushMessage')
            ->once()
            ->with(
                $lineUserId,
                Mockery::on(fn ($message) => is_array($message)
                    && ($message['type'] ?? '') === 'flex'
                    && ($message['contents']['hero']['type'] ?? '') === 'image'
                    && ($message['contents']['hero']['url'] ?? '') === $imageUrl),
                'test-token'
            )
            ->andReturn([]);

        $service = new CouponNotificationService($pushMock, $lineMock, app(CouponNotificationSettings::class), app(LiffTabUrlBuilder::class));
        $service->notifyIssued($memberCoupon);

        $this->addToAssertionCount(2);
    }

    public function test_notify_issued_omits_hero_when_image_url_is_not_public_https(): void
    {
        config(['app.url' => 'http://localhost']);
        $project = $this->createProject();
        LineSettingStore::setLineChannelAccessToken('test-token', $project->id);
        $lineUserId = 'U'.str_repeat('e', 32);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId,
        ]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => 'ローカル画像',
            'image_url' => 'http://localhost/storage/coupon.png',
        ]);
        $memberCoupon = MemberCoupon::withoutEvents(function () use ($project, $member, $coupon) {
            return MemberCoupon::factory()->create([
                'project_id' => $project->id,
                'member_id' => $member->id,
                'coupon_id' => $coupon->id,
                'coupon_name_snapshot' => 'ローカル画像',
            ]);
        });

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldReceive('sendToUser')
            ->once()
            ->andReturn(['target_tokens' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0]);

        $lineMock = Mockery::mock(LineService::class);
        $lineMock->shouldReceive('pushMessage')
            ->once()
            ->with(
                $lineUserId,
                Mockery::on(fn ($message) => is_array($message)
                    && ($message['type'] ?? '') === 'flex'
                    && ! isset($message['contents']['hero'])),
                'test-token'
            )
            ->andReturn([]);

        $service = new CouponNotificationService($pushMock, $lineMock, app(CouponNotificationSettings::class), app(LiffTabUrlBuilder::class));
        $service->notifyIssued($memberCoupon);

        $this->addToAssertionCount(2);
    }

    public function test_notify_issued_falls_back_to_default_image_when_coupon_image_is_not_public_https(): void
    {
        config(['app.url' => 'http://localhost']);
        $project = $this->createProject();
        LineSettingStore::setLineChannelAccessToken('test-token', $project->id);
        $defaultImageUrl = 'https://cdn.example.com/default-coupon.png';
        ShopSettingStore::setValue('coupon_default_image_url', $defaultImageUrl, 'string', $project->id);
        $lineUserId = 'U'.str_repeat('d', 32);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId,
        ]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => 'ローカル画像',
            'image_url' => 'http://localhost/storage/coupon.png',
        ]);
        $memberCoupon = MemberCoupon::withoutEvents(function () use ($project, $member, $coupon) {
            return MemberCoupon::factory()->create([
                'project_id' => $project->id,
                'member_id' => $member->id,
                'coupon_id' => $coupon->id,
                'coupon_name_snapshot' => 'ローカル画像',
            ]);
        });

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldReceive('sendToUser')
            ->once()
            ->andReturn(['target_tokens' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0]);

        $lineMock = Mockery::mock(LineService::class);
        $lineMock->shouldReceive('pushMessage')
            ->once()
            ->with(
                $lineUserId,
                Mockery::on(fn ($message) => is_array($message)
                    && ($message['type'] ?? '') === 'flex'
                    && ($message['contents']['hero']['url'] ?? '') === $defaultImageUrl),
                'test-token'
            )
            ->andReturn([]);

        $service = new CouponNotificationService($pushMock, $lineMock, app(CouponNotificationSettings::class), app(LiffTabUrlBuilder::class));
        $service->notifyIssued($memberCoupon);

        $this->addToAssertionCount(2);
    }

    public function test_notify_issued_クーポン機能無効時は通知を送らないこと(): void
    {
        $project = $this->createProject();
        ShopSettingStore::setValue('feature_coupons_enabled', '0', 'boolean', $project->id);
        LineSettingStore::setLineChannelAccessToken('test-token', $project->id);

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
            'push_token' => 'fcm-registration-token-test',
            'platform' => 'ios',
        ]);

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldNotReceive('sendToUser');

        $lineMock = Mockery::mock(LineService::class);
        $lineMock->shouldNotReceive('pushMessage');

        $service = new CouponNotificationService($pushMock, $lineMock, app(CouponNotificationSettings::class), app(LiffTabUrlBuilder::class));
        $service->notifyIssued($memberCoupon);

        $this->addToAssertionCount(2);
    }

    public function test_notify_issued_skips_when_notification_preference_is_none(): void
    {
        $project = $this->createProject();
        LineSettingStore::setLineChannelAccessToken('test-token', $project->id);
        $lineUserId = 'U'.str_repeat('c', 32);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId,
        ]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'notification_preference' => MemberNotificationPreference::NONE,
        ]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'name' => '拒否テスト']);
        $memberCoupon = MemberCoupon::withoutEvents(function () use ($project, $member, $coupon) {
            return MemberCoupon::factory()->create([
                'project_id' => $project->id,
                'member_id' => $member->id,
                'coupon_id' => $coupon->id,
                'coupon_name_snapshot' => '拒否テスト',
            ]);
        });

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldNotReceive('sendToUser');
        $lineMock = Mockery::mock(LineService::class);
        $lineMock->shouldNotReceive('pushMessage');

        $service = new CouponNotificationService($pushMock, $lineMock, app(CouponNotificationSettings::class), app(LiffTabUrlBuilder::class));
        $service->notifyIssued($memberCoupon);

        $this->addToAssertionCount(2);
    }

    private function issuedForRetry(): MemberCoupon
    {
        $project = $this->createProject();
        LineSettingStore::setLineChannelAccessToken('test-token', $project->id);
        $user = User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'U'.str_repeat('e', 32)]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'image_url' => null]);

        return MemberCoupon::withoutEvents(fn () => MemberCoupon::factory()->create([
            'project_id' => $project->id, 'member_id' => $member->id, 'coupon_id' => $coupon->id,
        ]));
    }

    public function test_failed_native_delivery_is_reported_after_line_is_attempted(): void
    {
        $issued = $this->issuedForRetry();
        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendToUser')->once()->andReturn(['target_tokens' => 2, 'sent' => 1, 'failed' => 1, 'skipped' => 0]);
        $line = Mockery::mock(LineService::class);
        $line->shouldReceive('pushMessage')->once()->andReturn([]);
        $this->expectException(\RuntimeException::class);
        (new CouponNotificationService($push, $line, app(CouponNotificationSettings::class), app(LiffTabUrlBuilder::class)))->notifyIssued($issued);
    }

    public function test_failed_line_delivery_is_reported_for_retry(): void
    {
        $issued = $this->issuedForRetry();
        $push = Mockery::mock(PushNotificationService::class);
        $push->shouldReceive('sendToUser')->once()->andReturn(['target_tokens' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0]);
        $line = Mockery::mock(LineService::class);
        $line->shouldReceive('pushMessage')->once()->andThrow(new \RuntimeException('Temporary LINE outage'));
        $this->expectException(\RuntimeException::class);
        (new CouponNotificationService($push, $line, app(CouponNotificationSettings::class), app(LiffTabUrlBuilder::class)))->notifyIssued($issued);
    }

    public function test_pending_notification_survives_failure_and_recovers_in_its_saved_project(): void
    {
        $issued = $this->issuedForRetry();
        $issued->forceFill(['notification_pending' => true, 'notification_retry_at' => now()])->save();
        $previous = Project::factory()->create();
        AdminContext::setCurrentProject($previous);
        $calls = 0;
        $notifications = $this->mock(CouponNotificationService::class, function ($mock) use ($issued, &$calls): void {
            $mock->shouldReceive('notifyIssued')->twice()->andReturnUsing(function ($coupon) use ($issued, &$calls): void {
                $this->assertSame((int) $issued->project_id, AdminContext::requireCurrentProjectId());
                if (++$calls === 1) {
                    throw new \RuntimeException('Temporary transport failure');
                }
            });
        });
        try {
            $job = new SendCouponIssuedNotificationJob((int) $issued->id);
            try {
                $job->handle($notifications);
                $this->fail('A worker must see the delivery failure.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('Temporary transport failure', $exception->getMessage());
            }
            $this->assertSame((int) $previous->id, AdminContext::requireCurrentProjectId());
            $this->assertDatabaseHas('member_coupons', ['id' => $issued->id, 'notification_pending' => true]);
            Queue::fake();
            $this->artisan('coupons:retry-notifications')->assertSuccessful();
            Queue::assertNotPushed(SendCouponIssuedNotificationJob::class);
            $this->travel(1)->minutes();
            $this->artisan('coupons:retry-notifications')->assertSuccessful();
            Queue::assertPushed(SendCouponIssuedNotificationJob::class, fn ($job) => $job->memberCouponId === (int) $issued->id);
            $job->handle($notifications);
            $this->assertDatabaseHas('member_coupons', ['id' => $issued->id, 'notification_pending' => false, 'notification_retry_at' => null]);
            $this->assertSame((int) $previous->id, AdminContext::requireCurrentProjectId());
        } finally {
            AdminContext::setCurrentProject(null);
        }
    }

    public function test_old_worker_cannot_clear_a_new_notification_lease(): void
    {
        $issued = $this->issuedForRetry();
        $issued->forceFill(['notification_pending' => true, 'notification_retry_at' => now()])->save();
        $notifications = $this->mock(CouponNotificationService::class, function ($mock) use ($issued): void {
            $mock->shouldReceive('notifyIssued')->once()->andReturnUsing(function () use ($issued): void {
                $issued->forceFill(['notification_retry_at' => now()->addMinutes(6)])->save();
            });
        });
        (new SendCouponIssuedNotificationJob((int) $issued->id))->handle($notifications);
        $this->assertDatabaseHas('member_coupons', ['id' => $issued->id, 'notification_pending' => true]);
        (new SendCouponIssuedNotificationJob((int) $issued->id))->handle($notifications);
    }

    public function test_enqueue_failure_cannot_undo_coupon_issuance_and_keeps_pending_work(): void
    {
        $template = $this->issuedForRetry();
        $this->mock(Dispatcher::class, function ($mock): void {
            $mock->shouldReceive('dispatch')->once()->andThrow(new \RuntimeException('Queue unavailable'));
        });
        $id = DB::transaction(function () use ($template): int {
            $issued = $template->replicate();
            $issued->save();

            return (int) $issued->id;
        });
        $this->assertDatabaseHas('member_coupons', ['id' => $id, 'notification_pending' => true]);
        $this->assertDatabaseCount('member_coupons', 2);
    }
}
