<?php

namespace Tests\Feature;

use App\Jobs\SendAfterRegistrationJob;
use App\Models\AdminUser;
use App\Models\AfterRegistrationDelivery;
use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_index_登録後配信一覧にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.after-registration-deliveries.index', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_create_登録後配信追加画面にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.after-registration-deliveries.create', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_store_登録後配信を追加できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.after-registration-deliveries.store', ['project_key' => $project->project_key]), [
                'delay_minutes' => 30,
                'title' => '登録後お知らせ',
                'body' => '本文',
                'is_active' => 1,
            ]);

        $response->assertRedirect(route('admin.after-registration-deliveries.index', ['project_key' => $project->project_key]));
        $this->assertDatabaseHas('after_registration_deliveries', [
            'project_id' => $project->id,
            'delay_minutes' => 30,
            'title' => '登録後お知らせ',
            'is_active' => true,
        ]);
    }

    public function test_SendAfterRegistrationJob_実行でお知らせとクーポンが作成されること(): void
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
        $job->handle(
            app(\App\Services\PushNotificationService::class),
            app(\App\Services\Admin\MemberAdminService::class)
        );

        $this->assertDatabaseHas('announcements', [
            'project_id' => $project->id,
            'title' => 'テスト',
            'delivery_type' => Announcement::DELIVERY_TYPE_AFTER_REGISTRATION,
        ]);
        $this->assertDatabaseHas('member_coupons', [
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
        ]);
    }
}
