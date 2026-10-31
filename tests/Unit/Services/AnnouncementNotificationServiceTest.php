<?php

namespace Tests\Unit\Services;

use App\Domain\Settings\Repositories\LineConnectionSettings;
use App\Enums\MemberNotificationPreference;
use App\Infrastructure\Persistence\Eloquent\Settings\LineSettingStore;
use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\LineDeliveryFailure;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\AnnouncementNotificationService;
use App\Services\CouponNotificationService;
use App\Services\Line\LineService;
use App\Services\PushNotificationService;
use App\Support\LiffTabUrlBuilder;
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

    public function test_line_payload_contains_all_ten_images_in_order(): void
    {
        $service = app(AnnouncementNotificationService::class);
        $method = new \ReflectionMethod($service, 'buildLinePayload');
        $urls = array_map(fn ($i) => 'https://example.com/'.$i.'.png', range(1, 10));
        $payload = $method->invoke($service, 'お知らせ', '本文', 'https://example.com/liff', $urls, new Announcement, 1, 'default', true);
        $this->assertSame('carousel', $payload['contents']['type']);
        $this->assertSame($urls, array_column(array_column($payload['contents']['contents'], 'hero'), 'url'));
        $fallback = $method->invoke($service, 'お知らせ', '本文', 'https://example.com/liff', [], new Announcement, 1, 'default', false);
        $this->assertSame('bubble', $fallback['contents']['type']);
        $this->assertArrayNotHasKey('hero', $fallback['contents']);
    }

    public function test_send_delivers_native_push_and_line_message(): void
    {
        $project = $this->createProject();
        LineSettingStore::setLineChannelAccessToken('test-token', $project->id);
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
                'notification_ids' => ['push-message-id'],
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

        $service = new AnnouncementNotificationService($pushMock, $lineMock, app(LineConnectionSettings::class), app(CouponNotificationService::class), app(LiffTabUrlBuilder::class));
        $result = $service->send($announcement);

        $this->assertSame(1, $result['sent']);
        $this->assertSame(1, $result['line_sent']);
        $this->assertSame(0, $result['line_failed']);
    }

    public function test_send_attaches_hero_image_for_https_image_url(): void
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
        ]);
        $imageUrl = 'https://cdn.example.com/announcement.png';
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '画像付きお知らせ',
            'body' => '本文',
            'image_url' => $imageUrl,
            'delivery_type' => Announcement::DELIVERY_TYPE_PERSONAL,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'target_member_ids' => [$member->id],
        ]);

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldReceive('sendAnnouncement')
            ->once()
            ->andReturn([
                'target_tokens' => 0,
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
                'notification_ids' => [],
            ]);

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

        $service = new AnnouncementNotificationService($pushMock, $lineMock, app(LineConnectionSettings::class), app(CouponNotificationService::class), app(LiffTabUrlBuilder::class));
        $result = $service->send($announcement);

        $this->assertSame(1, $result['line_sent']);
    }

    public function test_send_omits_hero_when_image_url_is_not_public_https(): void
    {
        config(['app.url' => 'http://localhost']);
        $project = $this->createProject();
        LineSettingStore::setLineChannelAccessToken('test-token', $project->id);
        $lineUserId = 'U'.str_repeat('d', 32);
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
            'title' => 'ローカル画像',
            'image_url' => 'http://localhost/storage/a.png',
            'delivery_type' => Announcement::DELIVERY_TYPE_PERSONAL,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'target_member_ids' => [$member->id],
        ]);

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldReceive('sendAnnouncement')
            ->once()
            ->andReturn([
                'target_tokens' => 0,
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
                'notification_ids' => [],
            ]);

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

        $service = new AnnouncementNotificationService($pushMock, $lineMock, app(LineConnectionSettings::class), app(CouponNotificationService::class), app(LiffTabUrlBuilder::class));
        $result = $service->send($announcement);

        $this->assertSame(1, $result['line_sent']);
    }

    public function test_send_drops_hero_for_remaining_members_after_image_retry_succeeds(): void
    {
        $project = $this->createProject();
        LineSettingStore::setLineChannelAccessToken('test-token', $project->id);
        $lineUserId1 = 'U'.str_repeat('1', 32);
        $lineUserId2 = 'U'.str_repeat('2', 32);
        $user1 = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId1,
        ]);
        $user2 = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId2,
        ]);
        $member1 = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user1->id,
        ]);
        $member2 = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user2->id,
        ]);
        $imageUrl = 'https://cdn.example.com/broken.png';
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '画像失敗後フォールバック',
            'body' => '本文',
            'image_url' => $imageUrl,
            'delivery_type' => Announcement::DELIVERY_TYPE_PERSONAL,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'target_member_ids' => [$member1->id, $member2->id],
        ]);

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldReceive('sendAnnouncement')
            ->once()
            ->andReturn([
                'target_tokens' => 0,
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
                'notification_ids' => [],
            ]);

        $withHero = 0;
        $withoutHero = 0;
        $lineMock = Mockery::mock(LineService::class);
        $lineMock->shouldReceive('pushMessage')
            ->times(3)
            ->with(
                Mockery::type('string'),
                Mockery::type('array'),
                'test-token'
            )
            ->andReturnUsing(function (string $lineUserId, array $message) use (&$withHero, &$withoutHero, $imageUrl): array {
                if (isset($message['contents']['hero'])) {
                    $withHero++;
                    $this->assertSame($imageUrl, $message['contents']['hero']['url'] ?? null);
                    throw new \RuntimeException('invalid image');
                }
                $withoutHero++;

                return [];
            });

        $service = new AnnouncementNotificationService($pushMock, $lineMock, app(LineConnectionSettings::class), app(CouponNotificationService::class), app(LiffTabUrlBuilder::class));
        $result = $service->send($announcement);

        $this->assertSame(2, $result['line_sent']);
        $this->assertSame(0, $result['line_failed']);
        $this->assertSame(1, $withHero);
        $this->assertSame(2, $withoutHero);
    }

    public function test_send_skips_line_when_member_has_no_line_user_id(): void
    {
        $project = $this->createProject();
        LineSettingStore::setLineChannelAccessToken('test-token', $project->id);
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

        $service = new AnnouncementNotificationService($pushMock, $lineMock, app(LineConnectionSettings::class), app(CouponNotificationService::class), app(LiffTabUrlBuilder::class));
        $result = $service->send($announcement);

        $this->assertSame(0, $result['line_sent']);
        $this->assertSame(0, $result['line_failed']);
    }

    public function test_send_ターゲット配信で対象が空なら誰にも送らないこと(): void
    {
        $project = $this->createProject();
        LineSettingStore::setLineChannelAccessToken('test-token', $project->id);
        $lineUserId = 'U'.str_repeat('a', 32);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId,
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '空ターゲット',
            'body' => '本文',
            'delivery_type' => Announcement::DELIVERY_TYPE_PERSONAL,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'target_member_ids' => [],
        ]);

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldReceive('sendAnnouncement')
            ->once()
            ->andReturn([
                'target_tokens' => 0,
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
                'notification_ids' => [],
            ]);

        $lineMock = Mockery::mock(LineService::class);
        $lineMock->shouldNotReceive('pushMessage');

        $service = new AnnouncementNotificationService($pushMock, $lineMock, app(LineConnectionSettings::class), app(CouponNotificationService::class), app(LiffTabUrlBuilder::class));
        $result = $service->send($announcement);

        $this->assertSame(0, $result['line_sent']);
        $this->assertSame(0, $result['line_failed']);
        $this->assertSame(0, $result['line_skipped']);
    }

    public function test_send_line_respects_member_notification_preference(): void
    {
        $project = $this->createProject();
        LineSettingStore::setLineChannelAccessToken('test-token', $project->id);
        $couponOnlyLineId = 'U'.str_repeat('e', 32);
        $noneLineId = 'U'.str_repeat('f', 32);
        $couponOnlyUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $couponOnlyLineId,
        ]);
        $noneUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $noneLineId,
        ]);
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
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'name' => '春の割引']);

        $withoutCoupon = Announcement::factory()->create([
            'project_id' => $project->id,
            'target_member_ids' => [$couponOnlyMember->id, $noneMember->id],
            'delivery_coupon_id' => null,
        ]);
        $withCoupon = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '春のお知らせ',
            'body' => 'キャンペーン本文です',
            'target_member_ids' => [$couponOnlyMember->id, $noneMember->id],
            'delivery_coupon_id' => $coupon->id,
        ]);

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldReceive('sendAnnouncement')->twice()->andReturn([
            'target_tokens' => 0,
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
            'notification_ids' => [],
        ]);
        $lineMock = Mockery::mock(LineService::class);
        $lineMock->shouldReceive('pushMessage')
            ->once()
            ->with(
                $couponOnlyLineId,
                Mockery::on(function ($payload) use ($coupon): bool {
                    $messages = isset($payload['type']) ? [$payload] : array_values($payload);
                    if (count($messages) < 2) {
                        return false;
                    }
                    $announcementJson = json_encode($messages[0], JSON_UNESCAPED_UNICODE) ?: '';
                    $couponJson = json_encode($messages[1], JSON_UNESCAPED_UNICODE) ?: '';

                    return str_contains($announcementJson, '春のお知らせ')
                        && str_contains($announcementJson, 'キャンペーン本文です')
                        && str_contains($couponJson, (string) $coupon->name);
                }),
                'test-token'
            )
            ->andReturn([]);

        $service = new AnnouncementNotificationService($pushMock, $lineMock, app(LineConnectionSettings::class), app(CouponNotificationService::class), app(LiffTabUrlBuilder::class));
        $this->assertSame(0, $service->send($withoutCoupon)['line_sent']);
        $this->assertSame(1, $service->send($withCoupon)['line_sent']);
    }

    public function test_send_ブロック中の会員には送らず送信エラーと合わせて履歴に残すこと(): void
    {
        $project = $this->createProject();
        LineSettingStore::setLineChannelAccessToken('test-token', $project->id);
        $blocked = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'U'.str_repeat('b', 32), 'line_blocked_at' => now()])->id,
        ]);
        $failing = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id, 'line_user_id' => 'U'.str_repeat('e', 32)])->id,
        ]);
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '履歴テスト',
            'delivery_type' => Announcement::DELIVERY_TYPE_PERSONAL,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'target_member_ids' => [$blocked->id, $failing->id],
        ]);

        $pushMock = Mockery::mock(PushNotificationService::class);
        $pushMock->shouldReceive('sendAnnouncement')->andReturn(['target_tokens' => 0, 'sent' => 0, 'failed' => 0, 'skipped' => 0, 'notification_ids' => []]);
        $lineMock = Mockery::mock(LineService::class);
        $lineMock->shouldReceive('pushMessage')->once()
            ->with('U'.str_repeat('e', 32), Mockery::any(), 'test-token')
            ->andThrow(new \RuntimeException('400 Bad Request'));

        $service = new AnnouncementNotificationService($pushMock, $lineMock, app(LineConnectionSettings::class), app(CouponNotificationService::class), app(LiffTabUrlBuilder::class));
        $result = $service->send($announcement);

        $this->assertSame(0, $result['line_sent']);
        $this->assertSame(1, $result['line_failed']);
        $this->assertSame(1, $result['line_skipped']);
        $this->assertDatabaseHas('line_delivery_failures', ['member_id' => $blocked->id, 'announcement_id' => $announcement->id, 'reason' => LineDeliveryFailure::REASON_BLOCKED]);
        $this->assertDatabaseHas('line_delivery_failures', ['member_id' => $failing->id, 'reason' => LineDeliveryFailure::REASON_ERROR, 'error_message' => '400 Bad Request']);
    }

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }
}
