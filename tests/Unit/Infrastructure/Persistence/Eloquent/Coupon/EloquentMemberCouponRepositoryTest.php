<?php

namespace Tests\Unit\Infrastructure\Persistence\Eloquent\Coupon;

use App\Domain\Coupon\Aggregates\MemberCoupon as MemberCouponAggregate;
use App\Infrastructure\Persistence\Eloquent\Coupon\EloquentMemberCouponRepository;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Models\User;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentMemberCouponRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    public function test_has_issued_他プロジェクトの発行を誤検知しないこと(): void
    {
        $projectA = Project::factory()->create();
        $projectB = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $projectA->id]);
        $member = Member::factory()->create([
            'project_id' => $projectA->id,
            'user_id' => $user->id,
        ]);
        $couponA = Coupon::factory()->create(['project_id' => $projectA->id]);
        $couponB = Coupon::factory()->create(['project_id' => $projectB->id]);

        MemberCoupon::factory()->create([
            'project_id' => $projectA->id,
            'member_id' => $member->id,
            'coupon_id' => $couponA->id,
            'status' => MemberCouponAggregate::STATUS_UNUSED,
        ]);

        AdminContext::setCurrentProject($projectB);
        $repository = app(EloquentMemberCouponRepository::class);

        $this->assertTrue($repository->hasIssued(
            (int) $member->id,
            (int) $couponA->id,
            (int) $projectA->id,
        ));
        $this->assertFalse($repository->hasIssued(
            (int) $member->id,
            (int) $couponA->id,
            (int) $projectB->id,
        ));
        $this->assertFalse($repository->hasIssued(
            (int) $member->id,
            (int) $couponB->id,
            (int) $projectB->id,
        ));
    }

    public function test_delete_unused_for_source_未使用のみ削除し他会員を残すこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
        $otherUser = User::factory()->create(['project_id' => $project->id]);
        $otherMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $otherUser->id,
        ]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'status' => MemberCouponAggregate::STATUS_UNUSED,
            'issue_source_type' => 'smaregi_transaction',
            'issue_source_id' => 'head-1',
        ]);
        $used = MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'status' => MemberCouponAggregate::STATUS_USED,
            'used_at' => now(),
            'issue_source_type' => 'smaregi_transaction',
            'issue_source_id' => 'head-1',
        ]);
        MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $otherMember->id,
            'coupon_id' => $coupon->id,
            'status' => MemberCouponAggregate::STATUS_UNUSED,
            'issue_source_type' => 'smaregi_transaction',
            'issue_source_id' => 'head-1',
        ]);

        $deleted = app(EloquentMemberCouponRepository::class)->deleteUnusedForSource(
            (int) $project->id,
            (int) $member->id,
            'smaregi_transaction',
            'head-1',
        );

        $this->assertSame(1, $deleted);
        $this->assertDatabaseHas('member_coupons', ['id' => $used->id]);
        $this->assertSame(1, MemberCoupon::query()->where('member_id', $otherMember->id)->count());
        $this->assertSame(0, MemberCoupon::query()
            ->where('member_id', $member->id)
            ->where('status', MemberCouponAggregate::STATUS_UNUSED)
            ->count());
    }
}
