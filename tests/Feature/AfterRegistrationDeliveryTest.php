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
use App\Models\Project;
use App\Models\ScheduledDeliverySend;
use App\Models\User;
use App\Services\ScheduledDeliverySendService;
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
}
