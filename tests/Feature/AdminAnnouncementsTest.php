<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\PushNotificationService;
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
        $response->assertSee('一斉配信');
        $response->assertSee('個人宛配信');
        $response->assertSee('セグメント配信');
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
        $response->assertDontSee('冬のキャンペーン');
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
        $response->assertSee('既読数');
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
        Member::factory()->create([
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
        $response->assertSee('既読一覧');
        $response->assertSee('既読詳細対象');
        $response->assertSee('佐藤花子');
        $response->assertSee('M-READ-1');
    }
}
