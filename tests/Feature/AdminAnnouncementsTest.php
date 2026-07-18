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

    public function test_store_delivery_属性セグメントで配信できること(): void
    {
        $project = $this->createProject();
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

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.announcements.delivery.store', ['project_key' => $project->project_key]), [
                'title' => '属性セグメント配信',
                'body' => '本文',
                'delivery_type' => Announcement::DELIVERY_TYPE_SEGMENT,
                'segment_channel' => 'line',
                'segment_birthday_month' => 7,
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
        $this->assertSame([$member->id], $announcement->target_member_ids);
        $this->assertSame('line', $announcement->segment_criteria['channel'] ?? null);
        $this->assertSame(7, $announcement->segment_criteria['birthday_month'] ?? null);
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

    public function test_delete_お知らせを削除できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $announcement = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '削除対象',
        ]);

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

    public function test_store_delivery_画像url付きで配信できること(): void
    {
        $project = $this->createProject();
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
}
