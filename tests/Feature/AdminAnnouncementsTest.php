<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Announcement;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_announcements_お知らせ一覧にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.announcements', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_delivery_form_配信画面にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.announcements.delivery.create', ['project_key' => $project->project_key]));

        $response->assertStatus(200);
    }

    public function test_store_delivery_一斉配信を登録できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.announcements.delivery.store', ['project_key' => $project->project_key]), [
                'title' => '配信テスト',
                'body' => '本文テスト',
                'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
                'is_pinned' => 1,
            ]);

        $response->assertRedirect(route('admin.announcements', ['project_key' => $project->project_key]));
        $this->assertDatabaseHas('announcements', [
            'project_id' => $project->id,
            'title' => '配信テスト',
            'delivery_type' => Announcement::DELIVERY_TYPE_IMMEDIATE_BULK,
            'delivery_status' => Announcement::DELIVERY_STATUS_SENT,
            'is_pinned' => 1,
        ]);
    }

    public function test_store_delivery_配信と同時にクーポンを配布できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $admin->projects()->sync([$project->id]);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);

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
        ]);
    }
}
