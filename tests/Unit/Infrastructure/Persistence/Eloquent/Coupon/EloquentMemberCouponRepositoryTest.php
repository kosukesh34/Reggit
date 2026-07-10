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
}
