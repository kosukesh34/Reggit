<?php

namespace Tests\Feature;

use App\Enums\ScheduledDeliveryCategory;
use App\Enums\ScheduledDeliveryContentType;
use App\Enums\ScheduledDeliveryType;
use App\Jobs\SendAfterRegistrationJob;
use App\Jobs\SendCouponIssuedNotificationJob;
use App\Models\AdminUser;
use App\Models\AfterRegistrationDelivery;
use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Models\ScheduledDeliverySend;
use App\Models\User;
use App\Services\ScheduledDeliverySendService;
use App\Services\ScheduledDeliveryTargetService;
use App\Support\AdminMemberIdentifier;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AfterRegistrationDeliveryTest extends TestCase
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
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
        $admin->projects()->sync([$project->id]);

        return $admin;
    }

    public function test_index_スケジュール配信一覧にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.after-registration-deliveries.index', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('スケジュール配信');
        $response->assertSee('条件達成配信');
    }

    public function test_create_スケジュール配信追加画面にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.after-registration-deliveries.create', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
        $response->assertSee('予約配信');
        $response->assertSee('来月誕生日の人');
        $response->assertSee('クーポンのみ');
        $response->assertSee('きっかけ');
        $response->assertSee('届ける内容');
    }

    public function test_store_新規入会後配信を追加できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.after-registration-deliveries.store', ['project_key' => $project->project_key]), [
                'schedule_type' => ScheduledDeliveryType::NEW_USER_MINUTES,
                'content_type' => ScheduledDeliveryContentType::ANNOUNCEMENT,
                'delay_minutes' => 30,
                'title' => '登録後お知らせ',
                'body' => '本文',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('admin.after-registration-deliveries.index', ['project_key' => $project->project_key]));
        $this->assertDatabaseHas('after_registration_deliveries', [
            'project_id' => $project->id,
            'schedule_type' => ScheduledDeliveryType::NEW_USER_MINUTES,
            'delay_minutes' => 30,
            'title' => '登録後お知らせ',
            'is_active' => true,
        ]);
    }

    public function test_送る相手は全会員なら絞り込み条件を保存せず顧客指定なら会員を固定すること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $target = Member::factory()->create(['project_id' => $project->id, 'user_id' => User::factory()->create(['project_id' => $project->id])->id]);
        $identifier = app(AdminMemberIdentifier::class)->value($target);
        $store = route('admin.after-registration-deliveries.store', ['project_key' => $project->project_key]);
        $input = [
            'schedule_type' => ScheduledDeliveryType::WEEKLY,
            'content_type' => ScheduledDeliveryContentType::ANNOUNCEMENT,
            'day_of_week' => 1,
            'title' => '週次のお知らせ',
            'segment_sex' => '1',
        ];
        $this->actingAs($admin, 'admin');

        $this->post($store, $input + ['audience_type' => 'all'])->assertSessionHasNoErrors();
        $this->assertArrayNotHasKey('segment_criteria', AfterRegistrationDelivery::query()->latest('id')->firstOrFail()->params);

        $this->post($store, $input + ['audience_type' => 'members', 'target_member_numbers' => $identifier."\nNOPE999"])
            ->assertSessionHasErrors(['target_member_numbers' => '見つからない会員があります：NOPE999']);
        $this->post($store, $input + ['audience_type' => 'members', 'target_member_numbers' => ' '])
            ->assertSessionHasErrors('target_member_numbers');

        $this->post($store, $input + ['audience_type' => 'members', 'target_member_numbers' => $identifier])->assertSessionHasNoErrors();
        $params = AfterRegistrationDelivery::query()->latest('id')->firstOrFail()->params;
        $this->assertSame([(int) $target->id], $params['target_member_ids']);
        $this->assertSame([$identifier], $params['target_member_numbers']);
        $this->assertArrayNotHasKey('segment_criteria', $params);
    }

    public function test_絞り込みを選んで条件がなければ保存せず顧客指定は大文字小文字を区別しないこと(): void
    {
        $project = $this->createProject();
        $this->actingAs($this->createAdmin($project), 'admin');
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => User::factory()->create(['project_id' => $project->id])->id, 'member_number' => 'Mabc0001']);
        $store = route('admin.after-registration-deliveries.store', ['project_key' => $project->project_key]);
        $input = ['schedule_type' => ScheduledDeliveryType::WEEKLY, 'content_type' => ScheduledDeliveryContentType::ANNOUNCEMENT, 'day_of_week' => 1, 'title' => '週次'];

        $this->post($store, $input + ['audience_type' => 'segment'])
            ->assertSessionHasErrors(['audience_type' => '絞り込みの条件を1つ以上追加してください。']);
        $this->assertSame(0, AfterRegistrationDelivery::query()->count());

        $this->post($store, $input + ['audience_type' => 'members', 'target_member_numbers' => 'MABC0001'])->assertSessionHasNoErrors();
        $params = AfterRegistrationDelivery::query()->sole()->params;
        $this->assertSame([[(int) $member->id], ['Mabc0001']], [$params['target_member_ids'], $params['target_member_numbers']]);
    }

    public function test_顧客指定の配信は指定した会員にだけ送ること(): void
    {
        $now = Carbon::parse('2026-11-16 10:00:00'); // 月曜
        $project = $this->createProject();
        $members = Member::factory()->count(2)->sequence(fn () => [
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
        ])->create();
        $delivery = AfterRegistrationDelivery::create([
            'project_id' => $project->id,
            'category' => ScheduledDeliveryCategory::RESERVATION,
            'schedule_type' => ScheduledDeliveryType::WEEKLY,
            'content_type' => ScheduledDeliveryContentType::ANNOUNCEMENT,
            'title' => '指定会員へ',
            'push_hour' => 10,
            'push_minute' => 0,
            'params' => ['day_of_week' => 1, 'target_member_ids' => [(int) $members[1]->id]],
            'is_active' => true,
        ]);

        $this->assertSame([(int) $members[1]->id], app(ScheduledDeliveryTargetService::class)->memberIds($delivery, $now));
    }

    public function test_store_クーポンのみの予約配信を追加できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.after-registration-deliveries.store', ['project_key' => $project->project_key]), [
                'schedule_type' => ScheduledDeliveryType::BIRTHDAY_NEXT_MONTH,
                'content_type' => ScheduledDeliveryContentType::COUPON_ONLY,
                'coupon_id' => $coupon->id,
                'send_notification' => 0,
                'day_of_month' => 1,
                'push_hour' => 10,
                'push_minute' => 0,
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('admin.after-registration-deliveries.index', ['project_key' => $project->project_key]));
        $delivery = AfterRegistrationDelivery::query()->where('project_id', $project->id)->first();
        $this->assertNotNull($delivery);
        $this->assertSame(ScheduledDeliveryCategory::RESERVATION, $delivery->category);
        $this->assertSame(ScheduledDeliveryType::BIRTHDAY_NEXT_MONTH, $delivery->schedule_type);
        $this->assertTrue($delivery->isCouponOnly());
        $this->assertFalse($delivery->send_notification);
        $this->assertSame($coupon->id, $delivery->coupon_id);
    }

    public function test_send_after_registration_job_実行でお知らせとクーポンが作成されること(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $delivery = AfterRegistrationDelivery::create([
            'project_id' => $project->id,
            'delay_minutes' => 30,
            'title' => 'テスト',
            'body' => '本文',
            'coupon_id' => $coupon->id,
            'is_active' => true,
        ]);

        $job = new SendAfterRegistrationJob($member->id, $delivery->id);
        $job->handle(app(ScheduledDeliverySendService::class));

        $this->assertDatabaseHas('announcements', [
            'project_id' => $project->id,
            'title' => 'テスト',
            'delivery_type' => Announcement::DELIVERY_TYPE_AFTER_REGISTRATION,
        ]);
        $this->assertDatabaseHas('member_coupons', [
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
        ]);
        $this->assertDatabaseHas('scheduled_delivery_sends', [
            'after_registration_delivery_id' => $delivery->id,
            'member_id' => $member->id,
            'send_key' => 'once',
        ]);
    }

    public function test_クーポンのみはお知らせを作らず通知も送らないこと(): void
    {
        Queue::fake();
        $project = $this->createProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $delivery = AfterRegistrationDelivery::create([
            'project_id' => $project->id,
            'content_type' => ScheduledDeliveryContentType::COUPON_ONLY,
            'title' => '',
            'coupon_id' => $coupon->id,
            'send_notification' => false,
            'is_active' => true,
        ]);

        app(ScheduledDeliverySendService::class)->sendToMember($delivery, $member);

        $this->assertDatabaseMissing('announcements', ['project_id' => $project->id]);
        $this->assertDatabaseHas('member_coupons', [
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
        ]);
        Queue::assertNotPushed(SendCouponIssuedNotificationJob::class);
    }

    public function test_クーポンのみで通知ありなら通知ジョブが飛ぶこと(): void
    {
        Queue::fake();
        $project = $this->createProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $delivery = AfterRegistrationDelivery::create([
            'project_id' => $project->id,
            'content_type' => ScheduledDeliveryContentType::COUPON_ONLY,
            'coupon_id' => $coupon->id,
            'send_notification' => true,
            'is_active' => true,
        ]);

        app(ScheduledDeliverySendService::class)->sendToMember($delivery, $member);

        $this->assertDatabaseMissing('announcements', ['project_id' => $project->id]);
        Queue::assertPushed(SendCouponIssuedNotificationJob::class);
    }

    public function test_毎月1日に来月誕生日の人へ予約配信できること(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-01 10:00:00'));
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $targetUser = User::factory()->create(['project_id' => $project->id]);
        $target = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $targetUser->id,
            'birthday' => '1990-09-15',
        ]);
        $otherUser = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $otherUser->id,
            'birthday' => '1990-08-15',
        ]);
        AfterRegistrationDelivery::create([
            'project_id' => $project->id,
            'category' => ScheduledDeliveryCategory::RESERVATION,
            'schedule_type' => ScheduledDeliveryType::BIRTHDAY_NEXT_MONTH,
            'content_type' => ScheduledDeliveryContentType::COUPON_ONLY,
            'coupon_id' => $coupon->id,
            'send_notification' => false,
            'push_hour' => 10,
            'push_minute' => 0,
            'params' => ['day_of_month' => 1],
            'is_active' => true,
        ]);

        try {
            $this->artisan('deliveries:send-scheduled')->assertSuccessful();

            $this->assertDatabaseHas('member_coupons', [
                'member_id' => $target->id,
                'coupon_id' => $coupon->id,
            ]);
            $this->assertSame(1, ScheduledDeliverySend::query()->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_同じ会員への再送は1回だけ記録されること(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $delivery = AfterRegistrationDelivery::create([
            'project_id' => $project->id,
            'content_type' => ScheduledDeliveryContentType::COUPON_ONLY,
            'coupon_id' => $coupon->id,
            'send_notification' => false,
            'is_active' => true,
        ]);

        $service = app(ScheduledDeliverySendService::class);
        $this->assertTrue($service->sendToMember($delivery, $member));
        $this->assertFalse($service->sendToMember($delivery, $member));

        $this->assertSame(1, ScheduledDeliverySend::query()->count());
        $this->assertSame(1, MemberCoupon::query()->where('member_id', $member->id)->count());
    }

    /** 毎週月曜10時の「クーポンのみ」配信 */
    private function weeklyCouponDelivery(Project $project, Coupon $coupon, array $attributes = []): AfterRegistrationDelivery
    {
        return AfterRegistrationDelivery::create($attributes + [
            'project_id' => $project->id,
            'category' => ScheduledDeliveryCategory::RESERVATION,
            'schedule_type' => ScheduledDeliveryType::WEEKLY,
            'content_type' => ScheduledDeliveryContentType::COUPON_ONLY,
            'coupon_id' => $coupon->id,
            'send_notification' => false,
            'push_hour' => 10,
            'push_minute' => 0,
            'params' => ['day_of_week' => 1],
            'is_active' => true,
        ]);
    }

    public function test_配信期間を日時で保存でき終了日時が開始日時以前なら保存しないこと(): void
    {
        $project = $this->createProject();
        $this->actingAs($this->createAdmin($project), 'admin');
        $input = [
            'schedule_type' => ScheduledDeliveryType::NEW_USER_MINUTES,
            'content_type' => ScheduledDeliveryContentType::ANNOUNCEMENT,
            'title' => '10月限定',
            'is_active' => 1,
        ];

        $this->post(route('admin.after-registration-deliveries.store', ['project_key' => $project->project_key]), $input + ['starts_at' => '2026-10-31T10:00', 'ends_at' => '2026-10-31T10:00'])
            ->assertSessionHasErrors('ends_at');
        $this->post(route('admin.after-registration-deliveries.store', ['project_key' => $project->project_key]), $input + ['starts_at' => '2026-10-01T09:30', 'ends_at' => '2026-10-31T23:59'])
            ->assertSessionHasNoErrors();

        $delivery = AfterRegistrationDelivery::query()->sole();
        $this->assertSame(['2026-10-01 09:30:00', '2026-10-31 23:59:59'], [$delivery->starts_at->format('Y-m-d H:i:s'), $delivery->ends_at->format('Y-m-d H:i:s')]);
    }

    public function test_配信期間の外では送らず期間内だけ送ること(): void
    {
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'end_at' => '2026-12-31 23:59:59']);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => User::factory()->create(['project_id' => $project->id])->id]);
        $this->weeklyCouponDelivery($project, $coupon, ['starts_at' => '2026-10-05 10:00:00', 'ends_at' => '2026-10-31 23:59:59']);

        try {
            // 2026-09-28・2026-11-02 は期間外、2026-10-05 10:00 は開始ちょうどで期間内の月曜
            foreach (['2026-09-28 10:00:00' => 0, '2026-10-05 10:00:00' => 1, '2026-11-02 10:00:00' => 1] as $moment => $expected) {
                Carbon::setTestNow(Carbon::parse($moment));
                $this->artisan('deliveries:send-scheduled')->assertSuccessful();
                $this->assertSame($expected, MemberCoupon::query()->where('member_id', $member->id)->count(), $moment);
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_一覧から一時停止と再開ができ停止中は送らないこと(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-05 10:00:00'));
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'end_at' => '2026-12-31 23:59:59']);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => User::factory()->create(['project_id' => $project->id])->id]);
        $delivery = $this->weeklyCouponDelivery($project, $coupon, ['starts_at' => '2026-10-05 10:30:00']);
        $this->actingAs($this->createAdmin($project), 'admin');
        $index = route('admin.after-registration-deliveries.index', ['project_key' => $project->project_key]);

        try {
            $this->get($index)->assertSee('開始前')->assertSee('一時停止');
            $this->post(route('admin.after-registration-deliveries.pause', ['project_key' => $project->project_key, 'id' => $delivery->id]))->assertRedirect($index);
            $this->assertFalse($delivery->fresh()->is_active);
            $this->get($index)->assertSee('一時停止中')->assertSee('再開');

            $delivery->update(['starts_at' => null]);
            $this->artisan('deliveries:send-scheduled')->assertSuccessful();
            $this->assertSame(0, MemberCoupon::query()->where('member_id', $member->id)->count());

            $this->post(route('admin.after-registration-deliveries.resume', ['project_key' => $project->project_key, 'id' => $delivery->id]))->assertRedirect($index);
            $this->assertTrue($delivery->fresh()->is_active);
            $this->get($index)->assertSee('配信中');
            $this->artisan('deliveries:send-scheduled')->assertSuccessful();
            $this->assertSame(1, MemberCoupon::query()->where('member_id', $member->id)->count());
        } finally {
            Carbon::setTestNow();
        }
    }
}
