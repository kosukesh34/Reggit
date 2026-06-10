<?php

namespace Tests\Feature;

use App\Jobs\SendCouponIssuedNotificationJob;
use App\Models\AdminUser;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AdminCouponBulkIssueTest extends TestCase
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

    public function test_bulk_issue_全員配布でmember_coupons作成とジョブ投入(): void
    {
        Queue::fake();

        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);

        $members = [];
        for ($i = 0; $i < 3; $i++) {
            $user = User::factory()->create(['project_id' => $project->id]);
            $members[] = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        }

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.coupons.bulk-issue.store', ['project_key' => $project->project_key]), [
                'coupon_id' => $coupon->id,
                'target' => 'all',
            ]);

        $response->assertRedirect(route('admin.coupons', ['project_key' => $project->project_key]));

        foreach ($members as $member) {
            $this->assertDatabaseHas('member_coupons', [
                'member_id' => $member->id,
                'coupon_id' => $coupon->id,
            ]);
        }

        Queue::assertPushed(SendCouponIssuedNotificationJob::class, 3);
    }

    public function test_bulk_issue_selectedで会員_i_d未入力はバリデーションエラー(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.coupons.bulk-issue.store', ['project_key' => $project->project_key]), [
                'coupon_id' => $coupon->id,
                'target' => 'selected',
                'member_ids_input' => '',
            ]);

        $response->assertSessionHasErrors('member_ids_input');
        $this->assertDatabaseCount('member_coupons', 0);
    }

    public function test_bulk_issue_selectedで無効な_i_dのみはバリデーションエラー(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.coupons.bulk-issue.store', ['project_key' => $project->project_key]), [
                'coupon_id' => $coupon->id,
                'target' => 'selected',
                'member_ids_input' => 'abc, xyz',
            ]);

        $response->assertSessionHasErrors('member_ids_input');
        $this->assertDatabaseCount('member_coupons', 0);
    }

    public function test_bulk_issue_selectedで存在しない_i_dのみはバリデーションエラー(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.coupons.bulk-issue.store', ['project_key' => $project->project_key]), [
                'coupon_id' => $coupon->id,
                'target' => 'selected',
                'member_ids_input' => '99999',
            ]);

        $response->assertSessionHasErrors('member_ids_input');
        $this->assertDatabaseCount('member_coupons', 0);
    }
}
