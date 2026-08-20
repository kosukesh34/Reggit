<?php

namespace Tests\Feature;

use App\Enums\AnnouncementAudienceResolveMode;
use App\Enums\MemberCouponSource;
use App\Enums\MemberNotificationPreference;
use App\Enums\OrderStatus;
use App\Jobs\SendCouponIssuedNotificationJob;
use App\Jobs\SendScheduledAnnouncementJob;
use App\Models\AdminUser;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\MemberTag;
use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Admin\AnnouncementAdminService;
use App\Services\Admin\PublicImageStorageService;
use App\Services\AnnouncementNotificationService;
use App\Services\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAnnouncementsTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function createAdmin(Project $project): AdminUser
    {
        return AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
    }

    private function createDistributionProject(): Project
    {
        $project = Project::factory()->create(['plan' => 'distribution']);

        return $project;
    }

    public function test_announcements_お知らせ一覧にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.announcements', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('スケジュール配信');
        $response->assertSee('お知らせを配信');
        $response->assertDontSee('> 追加</a>', false);
        $response->assertSee('全会員');
        $response->assertSee('個別指定');
        $response->assertSee('条件指定');
    }

    public function test_announcements_配信形態で絞り込みできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '一斉のみ',
            'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
        ]);
        Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => 'セグメントのみ',
            'delivery_type' => Announcement::DELIVERY_TYPE_SEGMENT,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.announcements', [
                'project_key' => $project->project_key,
                'delivery_type' => Announcement::DELIVERY_TYPE_SEGMENT,
            ]));

        $response->assertStatus(200);
        $response->assertSee('セグメントのみ');
        $response->assertDontSee('一斉のみ');
    }

    public function test_announcements_期間とキーワードで検索できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '夏のキャンペーン',
            'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'published_at' => '2026-07-10 10:00:00',
        ]);
        Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '冬のキャンペーン',
            'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'published_at' => '2026-01-10 10:00:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.announcements', [
                'project_key' => $project->project_key,
                'keyword' => '夏',
                'published_from' => '2026-07-01',
                'published_to' => '2026-07-31',
            ]));

        $response->assertStatus(200);
        $response->assertSee('夏のキャンペーン');
        $response->assertSee('2026/07/10 10:00');
        $response->assertDontSee('冬のキャンペーン');
    }

    public function test_delivery_form_配信画面にアクセスできること(): void
    {
        $project = $this->createDistributionProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => '配信画面テストクーポン',
            'is_active' => true,
        ]);
        MemberTag::create([
            'project_id' => $project->id,
            'name' => 'VIP',
            'color' => '#7c3aed',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.announcements.delivery.create', [
                'project_key' => $project->project_key,
                'coupon_id' => $coupon->id,
            ]));

        $response->assertStatus(200)
            ->assertSee('name="image_file"', false)
            ->assertSee('ドラッグ＆ドロップ')
            ->assertSee('タイトル')
            ->assertDontSee('お知らせの内容をつくる')
            ->assertDontSee('会員がひと目で内容を理解できる')
            ->assertSee('name="delivery_timing"', false)
            ->assertSee('即時配信')
            ->assertSee('予約配信')
            ->assertSee('今の対象者で固定する')
            ->assertSee('配信時に条件を再評価する')
            ->assertSee('name="audience_type"', false)
            ->assertSee('name="segment_birthday_months[]"', false)
            ->assertSee('name="segment_tag_ids[]"', false)
            ->assertSee('name="segment_rfm_segments[]"', false)
            ->assertSee('RFMセグメント')
            ->assertSee('複数選択可')
            ->assertSee('配信対象者一覧')
            ->assertSee('id="deliverySummaryConditionBlock"', false)
            ->assertSee('すべての条件に一致する会員へ配信します')
            ->assertDontSee('クーポンを一緒に届ける')
            ->assertDontSee('選択したクーポンを、お知らせの配信対象と同じ会員へ1枚ずつ付与します。')
            ->assertDontSee('掲載方法を整える')
            ->assertDontSee('重要なお知らせは上部に固定し、必要に応じて掲載終了日を設定できます。')
            ->assertDontSee('届け先とタイミングを選ぶ')
            ->assertDontSee('誰に、いつ届けるかを選択します。セグメントでは条件に合う人数を確認できます。')
            ->assertSee('入力内容')
            ->assertSee('LINE・Push通知を送る')
            ->assertSee('配信後の見え方をプレビュー')
            ->assertSee('配信内容の最終確認')
            ->assertSee('実際の会員アプリのお知らせ画面')
            ->assertSee('配信予定ユーザー')
            ->assertSee('LINE')
            ->assertSee('Push通知')
            ->assertSee('配信画面テストクーポン')
            ->assertSee('value="'.$coupon->id.'"', false)
            ->assertSee('selected', false)
            ->assertDontSee('STEP 01')
            ->assertDontSee('NEW DELIVERY')
            ->assertDontSee('届けたい会員へ');
    }

    public function test_create_form_追加画面は一覧へリダイレクトすること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.announcements.create', ['project_key' => $project->project_key]));

        $response->assertRedirect(route('admin.announcements', ['project_key' => $project->project_key]));
    }

    public function test_store_delivery_一斉配信を登録できること(): void
    {
        $project = $this->createDistributionProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.announcements.delivery.store', ['project_key' => $project->project_key]), [
                'title' => '配信テスト',
                'body' => '本文テスト',
                'audience_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
                'delivery_timing' => Announcement::DELIVERY_TIMING_IMMEDIATE,
                'is_pinned' => 1,
            ]);

        $response->assertRedirect(route('admin.announcements', ['project_key' => $project->project_key]));
        $this->assertDatabaseHas('announcements', [
            'project_id' => $project->id,
            'title' => '配信テスト',
            'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
            'delivery_timing' => Announcement::DELIVERY_TIMING_IMMEDIATE,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'is_pinned' => 1,
        ]);
        $announcement = Announcement::where('title', '配信テスト')->firstOrFail();
        $this->assertSame([$member->id], $announcement->target_member_ids);
        $this->assertTrue($announcement->send_notification);
    }

    public function test_store_delivery_通知オフなら通知を送らないこと(): void
    {
        $this->mock(AnnouncementNotificationService::class, function ($mock): void {
            $mock->shouldNotReceive('send');
        });
        $project = $this->createDistributionProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.announcements.delivery.store', ['project_key' => $project->project_key]), [
                'title' => '通知オフ配信',
                'body' => '本文',
                'audience_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
                'delivery_timing' => Announcement::DELIVERY_TIMING_IMMEDIATE,
                'is_pinned' => 0,
                'send_notification' => 0,
            ]);

        $response->assertRedirect(route('admin.announcements', ['project_key' => $project->project_key]));
        $announcement = Announcement::where('title', '通知オフ配信')->firstOrFail();
        $this->assertFalse($announcement->send_notification);
        $this->assertSame(Announcement::DELIVERY_STATUS_SENT, $announcement->delivery_status);
    }

    public function test_store_delivery_予約配信を個別会員へ登録できること(): void
    {
        Bus::fake();
        $project = $this->createDistributionProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $scheduledAt = now()->addHours(2)->format('Y-m-d H:i:s');

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.announcements.delivery.store', ['project_key' => $project->project_key]), [
                'title' => '予約配信テスト',
                'body' => '本文テスト',
                'audience_type' => Announcement::DELIVERY_TYPE_PERSONAL,
                'delivery_timing' => Announcement::DELIVERY_TIMING_SCHEDULED,
                'scheduled_at' => $scheduledAt,
                'target_member_ids' => [$member->id],
                'is_pinned' => 0,
            ]);

        $response->assertRedirect(route('admin.announcements', ['project_key' => $project->project_key]));
        $this->assertDatabaseHas('announcements', [
            'project_id' => $project->id,
            'title' => '予約配信テスト',
            'delivery_type' => Announcement::DELIVERY_TYPE_PERSONAL,
            'delivery_timing' => Announcement::DELIVERY_TIMING_SCHEDULED,
            'delivery_status' => Announcement::DELIVERY_STATUS_SCHEDULED,
        ]);
        $announcement = Announcement::where('title', '予約配信テスト')->firstOrFail();
        $this->assertSame([$member->id], $announcement->target_member_ids);
        Bus::assertDispatched(SendScheduledAnnouncementJob::class);
    }

    public function test_store_delivery_予約配信で対象者を配信時に再評価できること(): void
    {
        Bus::fake();
        $project = $this->createDistributionProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'birthday' => '1990-07-15',
        ]);
        $scheduledAt = now()->addHours(2)->format('Y-m-d H:i:s');

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.announcements.delivery.store', ['project_key' => $project->project_key]), [
                'title' => '配信時再評価',
                'body' => '本文',
                'audience_type' => Announcement::DELIVERY_TYPE_SEGMENT,
                'delivery_timing' => Announcement::DELIVERY_TIMING_SCHEDULED,
                'scheduled_at' => $scheduledAt,
                'audience_resolve_mode' => AnnouncementAudienceResolveMode::AT_SEND,
                'segment_birthday_months' => [7],
                'is_pinned' => 0,
            ]);

        $response->assertRedirect(route('admin.announcements', ['project_key' => $project->project_key]));
        $announcement = Announcement::where('title', '配信時再評価')->firstOrFail();
        $this->assertNull($announcement->target_member_ids);
        $this->assertSame(AnnouncementAudienceResolveMode::AT_SEND, $announcement->audience_resolve_mode);
        $this->assertSame([7], $announcement->segment_criteria['birthday_months'] ?? null);
        $this->assertTrue($announcement->shouldResolveAudienceAtSend());

        $member->update(['birthday' => '1990-08-15']);
        $newUser = User::factory()->create(['project_id' => $project->id]);
        $newMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $newUser->id,
            'birthday' => '1991-07-20',
        ]);

        $job = new SendScheduledAnnouncementJob($announcement->id);
        $job->handle(
            app(AnnouncementNotificationService::class),
            app(AnnouncementAdminService::class),
        );

        $announcement->refresh();
        $this->assertSame(Announcement::DELIVERY_STATUS_SENT, $announcement->delivery_status);
        $this->assertSame([$newMember->id], $announcement->target_member_ids);
        $this->assertNotContains($member->id, $announcement->target_member_ids ?? []);
    }

    public function test_store_delivery_予約配信の対象者固定は作成時点の会員を保存すること(): void
    {
        Bus::fake();
        $project = $this->createDistributionProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'birthday' => '1990-07-15',
        ]);
        $scheduledAt = now()->addHours(2)->format('Y-m-d H:i:s');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.announcements.delivery.store', ['project_key' => $project->project_key]), [
                'title' => '対象者固定',
                'body' => '本文',
                'audience_type' => Announcement::DELIVERY_TYPE_SEGMENT,
                'delivery_timing' => Announcement::DELIVERY_TIMING_SCHEDULED,
                'scheduled_at' => $scheduledAt,
                'audience_resolve_mode' => AnnouncementAudienceResolveMode::SNAPSHOT,
                'segment_birthday_months' => [7],
                'is_pinned' => 0,
            ])
            ->assertRedirect();

        $laterUser = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $laterUser->id,
            'birthday' => '1992-07-01',
        ]);

        $announcement = Announcement::where('title', '対象者固定')->firstOrFail();
        $this->assertSame([$member->id], $announcement->target_member_ids);
        $this->assertSame(AnnouncementAudienceResolveMode::SNAPSHOT, $announcement->audience_resolve_mode);

        $job = new SendScheduledAnnouncementJob($announcement->id);
        $job->handle(
            app(AnnouncementNotificationService::class),
            app(AnnouncementAdminService::class),
        );

        $announcement->refresh();
        $this->assertSame([$member->id], $announcement->target_member_ids);
    }

    public function test_予約配信ジョブを二度実行しても送信済みのままであること(): void
    {
        $project = $this->createDistributionProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '二重実行防止',
            'delivery_type' => Announcement::DELIVERY_TYPE_PERSONAL,
            'delivery_timing' => Announcement::DELIVERY_TIMING_SCHEDULED,
            'delivery_status' => Announcement::DELIVERY_STATUS_SCHEDULED,
            'target_member_ids' => [$member->id],
            'send_notification' => false,
        ]);

        $job = new SendScheduledAnnouncementJob($announcement->id);
        $job->handle(
            app(AnnouncementNotificationService::class),
            app(AnnouncementAdminService::class),
        );
        $job->handle(
            app(AnnouncementNotificationService::class),
            app(AnnouncementAdminService::class),
        );

        $announcement->refresh();
        $this->assertSame(Announcement::DELIVERY_STATUS_SENT, $announcement->delivery_status);
        $this->assertSame([$member->id], $announcement->target_member_ids);
    }

    public function test_予約配信ジョブは送信済みにしたあと通知すること(): void
    {
        $project = $this->createDistributionProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '送信後通知',
            'delivery_type' => Announcement::DELIVERY_TYPE_PERSONAL,
            'delivery_timing' => Announcement::DELIVERY_TIMING_SCHEDULED,
            'delivery_status' => Announcement::DELIVERY_STATUS_SCHEDULED,
            'target_member_ids' => [$member->id],
            'send_notification' => true,
        ]);
        $sentStatus = null;
        $this->mock(AnnouncementNotificationService::class, function ($mock) use ($announcement, &$sentStatus): void {
            $mock->shouldReceive('send')->once()->andReturnUsing(function () use ($announcement, &$sentStatus): array {
                $sentStatus = $announcement->fresh()->delivery_status;

                return [
                    'target_tokens' => 0,
                    'sent' => 0,
                    'failed' => 0,
                    'skipped' => 0,
                    'notification_ids' => [],
                    'line_sent' => 0,
                    'line_failed' => 0,
                    'line_skipped' => 0,
                ];
            });
        });

        (new SendScheduledAnnouncementJob($announcement->id))->handle(
            app(AnnouncementNotificationService::class),
            app(AnnouncementAdminService::class),
        );

        $this->assertSame(Announcement::DELIVERY_STATUS_SENT, $sentStatus);
        $this->assertSame(Announcement::DELIVERY_STATUS_SENT, $announcement->fresh()->delivery_status);
    }

    public function test_store_delivery_配信と同時にクーポンを配布できること(): void
    {
        Queue::fake();
        $project = $this->createDistributionProject();
        $admin = $this->createAdmin($project);
        $admin->projects()->sync([$project->id]);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $this->mock(AnnouncementNotificationService::class, function ($mock) use ($member, $coupon): void {
            $mock->shouldReceive('send')->once()->andReturnUsing(function () use ($member, $coupon): array {
                $this->assertDatabaseHas('member_coupons', [
                    'member_id' => $member->id,
                    'coupon_id' => $coupon->id,
                ]);

                return [
                    'target_tokens' => 0,
                    'sent' => 0,
                    'failed' => 0,
                    'skipped' => 0,
                    'notification_ids' => [],
                    'line_sent' => 0,
                    'line_failed' => 0,
                    'line_skipped' => 0,
                ];
            });
        });

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.announcements.delivery.store', ['project_key' => $project->project_key]), [
                'title' => 'クーポン付き配信',
                'body' => '本文',
                'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
                'delivery_coupon_id' => $coupon->id,
                'is_pinned' => 0,
            ]);

        $response->assertRedirect(route('admin.announcements', ['project_key' => $project->project_key]));
        $this->assertDatabaseHas('announcements', [
            'project_id' => $project->id,
            'title' => 'クーポン付き配信',
            'delivery_coupon_id' => $coupon->id,
        ]);
        $this->assertDatabaseHas('member_coupons', [
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'issue_source_type' => MemberCouponSource::ANNOUNCEMENT,
            'issue_source_id' => (string) Announcement::where('title', 'クーポン付き配信')->value('id'),
        ]);
        Queue::assertNotPushed(SendCouponIssuedNotificationJob::class);
    }

    public function test_store_delivery_属性セグメントで配信できること(): void
    {
        $project = $this->createDistributionProject();
        $admin = $this->createAdmin($project);
        $lineUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U'.str_repeat('b', 32),
        ]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $lineUser->id,
            'birthday' => '1992-07-01',
        ]);
        $tag = MemberTag::create([
            'project_id' => $project->id,
            'name' => '夏キャンペーン',
            'color' => '#0f766e',
        ]);
        $member->tags()->attach($tag->id);
        $otherMonthUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U'.str_repeat('d', 32),
        ]);
        $otherMonthMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $otherMonthUser->id,
            'birthday' => '1990-08-10',
        ]);
        $otherMonthMember->tags()->attach($tag->id);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.announcements.delivery.store', ['project_key' => $project->project_key]), [
                'title' => '属性セグメント配信',
                'body' => '本文',
                'delivery_type' => Announcement::DELIVERY_TYPE_SEGMENT,
                'segment_channel' => 'line',
                'segment_birthday_months' => [7, 8],
                'segment_tag_ids' => [$tag->id],
                'is_pinned' => 0,
            ]);

        $response->assertRedirect(route('admin.announcements', ['project_key' => $project->project_key]));
        $this->assertDatabaseHas('announcements', [
            'project_id' => $project->id,
            'title' => '属性セグメント配信',
            'delivery_type' => Announcement::DELIVERY_TYPE_SEGMENT,
        ]);

        $announcement = Announcement::where('title', '属性セグメント配信')->first();
        $this->assertNotNull($announcement);
        $this->assertEqualsCanonicalizing([$member->id, $otherMonthMember->id], $announcement->target_member_ids);
        $this->assertSame('line', $announcement->segment_criteria['channel'] ?? null);
        $this->assertSame([7, 8], $announcement->segment_criteria['birthday_months'] ?? null);
        $this->assertSame([$tag->id], $announcement->segment_criteria['tag_ids'] ?? null);
    }

    public function test_store_delivery_rfmセグメントで配信できること(): void
    {
        $project = $this->createDistributionProject();
        $admin = $this->createAdmin($project);
        $neverUser = User::factory()->create(['project_id' => $project->id]);
        $neverMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $neverUser->id,
            'status' => 'active',
        ]);
        $activeUser = User::factory()->create(['project_id' => $project->id]);
        $activeMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $activeUser->id,
            'status' => 'active',
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $activeUser->id,
            'order_number' => 'ORD-'.uniqid(),
            'order_type' => 'take_out',
            'total_amount' => 1000,
            'status' => OrderStatus::COMPLETED,
        ]);
        $order->forceFill(['created_at' => now()->subDays(5)])->save();

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.announcements.delivery.store', ['project_key' => $project->project_key]), [
                'title' => 'RFM未購買配信',
                'body' => '本文',
                'audience_type' => Announcement::DELIVERY_TYPE_SEGMENT,
                'segment_rfm_segments' => ['never'],
                'is_pinned' => 0,
            ]);

        $response->assertRedirect(route('admin.announcements', ['project_key' => $project->project_key]));
        $announcement = Announcement::where('title', 'RFM未購買配信')->firstOrFail();
        $this->assertSame(['never'], $announcement->segment_criteria['rfm_segments'] ?? null);
        $this->assertSame([$neverMember->id], $announcement->target_member_ids);
        $this->assertNotContains($activeMember->id, $announcement->target_member_ids ?? []);
    }

    public function test_segment_preview_rfm条件の件数を返すこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.announcements.segment.preview', [
                'project_key' => $project->project_key,
                'segment_rfm_segments' => ['never'],
            ]));

        $response->assertOk();
        $response->assertJson(['count' => 1]);
    }

    public function test_segment_preview_属性条件の件数を返すこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'U'.str_repeat('c', 32),
        ]);
        $user = User::where('project_id', $project->id)->where('line_user_id', 'U'.str_repeat('c', 32))->first();
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.announcements.segment.preview', [
                'project_key' => $project->project_key,
                'segment_channel' => 'line',
            ]));

        $response->assertOk();
        $response->assertJson(['count' => 1]);
    }

    public function test_audience_preview_配信対象会員の一覧を返すこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $firstUser = User::factory()->create([
            'project_id' => $project->id,
            'name' => '対象 会員A',
            'line_user_id' => 'U'.str_repeat('a', 32),
        ]);
        $secondUser = User::factory()->create(['project_id' => $project->id, 'name' => '対象 会員B']);
        UserDevice::create([
            'project_id' => $project->id,
            'user_id' => $secondUser->id,
            'platform' => 'ios',
            'push_token' => 'announcement-preview-push-token',
        ]);
        $firstMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $firstUser->id,
            'member_number' => 'TARGET-001',
        ]);
        $secondMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $secondUser->id,
            'member_number' => 'TARGET-002',
            'notification_preference' => MemberNotificationPreference::COUPON_ONLY,
        ]);
        $tag = MemberTag::create([
            'project_id' => $project->id,
            'name' => 'VIP',
            'color' => '#7c3aed',
        ]);
        $firstMember->tags()->attach($tag->id);
        $otherProject = Project::factory()->create();
        Member::factory()->create(['project_id' => $otherProject->id]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.announcements.audience.preview', [
                'project_key' => $project->project_key,
                'audience_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
            ]));

        $response->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('members.0.id', $firstMember->id)
            ->assertJsonPath('members.0.name', '対象 会員A')
            ->assertJsonPath('members.0.tags.0.name', 'VIP')
            ->assertJsonPath('members.0.notification_sendable', true)
            ->assertJsonPath('members.1.id', $secondMember->id)
            ->assertJsonPath('members.1.notification_preference', MemberNotificationPreference::COUPON_ONLY)
            ->assertJsonPath('members.1.notification_sendable', false)
            ->assertJsonPath('notification_channels.line', 1)
            ->assertJsonPath('notification_channels.push', 0)
            ->assertJsonPath('notification_channels.unreachable', 0)
            ->assertJsonPath('notification_channels.opted_out', 1)
            ->assertJsonMissing(['member_number' => 'TARGET-OTHER']);

        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $this->actingAs($admin, 'admin')
            ->get(route('admin.announcements.audience.preview', [
                'project_key' => $project->project_key,
                'audience_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
                'delivery_coupon_id' => $coupon->id,
            ]))
            ->assertOk()
            ->assertJsonPath('members.1.notification_sendable', true)
            ->assertJsonPath('notification_channels.push', 1)
            ->assertJsonPath('notification_channels.opted_out', 0);
    }

    public function test_delete_お知らせを削除できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '削除対象',
        ]);

        $this->mock(PushNotificationService::class, function ($mock) {
            $mock->shouldReceive('cancelAnnouncementPush')->once();
        });

        $response = $this->actingAs($admin, 'admin')
            ->delete(route('admin.announcements.delete', [
                'project_key' => $project->project_key,
                'id' => $announcement->id,
            ]));

        $response->assertRedirect(route('admin.announcements', ['project_key' => $project->project_key]));
        $this->assertDatabaseMissing('announcements', ['id' => $announcement->id]);
    }

    public function test_update_画像urlを更新できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '更新前',
            'body' => '本文',
            'image_url' => null,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->put(route('admin.announcements.update', [
                'project_key' => $project->project_key,
                'id' => $announcement->id,
            ]), [
                'title' => '更新後',
                'body' => '本文更新',
                'image_url' => 'https://example.com/announcement.png',
                'is_pinned' => 1,
            ]);

        $response->assertRedirect(route('admin.announcements', ['project_key' => $project->project_key]));
        $this->assertDatabaseHas('announcements', [
            'id' => $announcement->id,
            'title' => '更新後',
            'body' => '本文更新',
            'image_url' => 'https://example.com/announcement.png',
            'is_pinned' => 1,
        ]);
    }

    public function test_update_画像ファイルをアップロードできること(): void
    {
        Storage::fake('public');
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '更新前',
            'body' => '本文',
            'image_url' => null,
        ]);
        $file = UploadedFile::fake()->image('announcement.png', 40, 40);

        $this->actingAs($admin, 'admin')
            ->put(route('admin.announcements.update', [
                'project_key' => $project->project_key,
                'id' => $announcement->id,
            ]), [
                'title' => '更新後',
                'body' => '本文更新',
                'image_file' => $file,
                'is_pinned' => 1,
            ])
            ->assertRedirect(route('admin.announcements', ['project_key' => $project->project_key]));

        $announcement->refresh();
        $this->assertStringContainsString(
            'storage/'.PublicImageStorageService::DIR_ANNOUNCEMENT.'/'.$project->id,
            (string) $announcement->image_url
        );
    }

    public function test_store_delivery_画像url付きで配信できること(): void
    {
        $project = $this->createDistributionProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.announcements.delivery.store', ['project_key' => $project->project_key]), [
                'title' => '画像付き配信',
                'body' => '本文',
                'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
                'image_url' => 'https://example.com/delivery.png',
                'is_pinned' => 0,
            ]);

        $response->assertRedirect(route('admin.announcements', ['project_key' => $project->project_key]));
        $this->assertDatabaseHas('announcements', [
            'project_id' => $project->id,
            'title' => '画像付き配信',
            'image_url' => 'https://example.com/delivery.png',
        ]);
    }

    public function test_announcements_既読数が表示されること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '既読確認用',
        ]);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'name' => '山田太郎',
        ]);
        AnnouncementRead::create([
            'project_id' => $project->id,
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.announcements', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('既読');
        $response->assertSee('1人');
    }

    public function test_announcement_reads_既読者一覧を表示できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '既読詳細対象',
        ]);
        $user = User::factory()->create([
            'project_id' => $project->id,
            'name' => '佐藤花子',
        ]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'member_number' => 'M-READ-1',
        ]);
        AnnouncementRead::create([
            'project_id' => $project->id,
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.announcements.reads', [
                'project_key' => $project->project_key,
                'id' => $announcement->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('配信結果');
        $response->assertSee('配信対象者');
        $response->assertSee('既読詳細対象');
        $response->assertSee('佐藤花子');
        $response->assertSee('M-READ-1');
        $response->assertSee(route('admin.members.detail', [
            'project_key' => $project->project_key,
            'id' => $member->id,
        ]), false);
    }

    public function test_announcement_results_配信対象と既読とクーポン利用状況を表示できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $firstUser = User::factory()->create(['project_id' => $project->id, 'name' => '利用 会員']);
        $secondUser = User::factory()->create(['project_id' => $project->id, 'name' => '未利用 会員']);
        $firstMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $firstUser->id,
            'member_number' => 'RESULT-001',
        ]);
        $secondMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $secondUser->id,
            'member_number' => 'RESULT-002',
        ]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '結果確認配信',
            'delivery_coupon_id' => $coupon->id,
            'target_member_ids' => [$firstMember->id, $secondMember->id],
            'sent_at' => now(),
        ]);
        AnnouncementRead::create([
            'project_id' => $project->id,
            'announcement_id' => $announcement->id,
            'user_id' => $firstUser->id,
            'read_at' => now(),
        ]);
        MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $firstMember->id,
            'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_USED,
            'used_at' => now(),
            'issue_source_type' => MemberCouponSource::ANNOUNCEMENT,
            'issue_source_id' => (string) $announcement->id,
        ]);
        MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $secondMember->id,
            'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_UNUSED,
            'issue_source_type' => MemberCouponSource::ANNOUNCEMENT,
            'issue_source_id' => (string) $announcement->id,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.announcements.reads', [
                'project_key' => $project->project_key,
                'id' => $announcement->id,
            ]));

        $response->assertOk()
            ->assertSee('結果確認配信')
            ->assertSee('利用 会員')
            ->assertSee('未利用 会員')
            ->assertSee('利用済み')
            ->assertSee('未利用')
            ->assertSee('50.0%');
    }
}
