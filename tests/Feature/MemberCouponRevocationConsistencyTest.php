<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Services\MemberBenefitService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MemberCouponRevocationConsistencyTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_revocation_preserves_a_coupon_used_after_an_outer_snapshot(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL repeatable-read snapshots.');
        }

        $project = Project::factory()->create();
        $member = Member::factory()->create(['project_id' => $project->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $issued = MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_UNUSED,
        ]);
        config(['database.connections.revocation_writer' => config('database.connections.'.DB::getDefaultConnection())]);
        $writer = DB::connection('revocation_writer');
        DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        DB::beginTransaction();
        try {
            $this->assertSame(MemberCoupon::STATUS_UNUSED, $issued->fresh()->status);
            $writer->table('member_coupons')->where('id', $issued->id)->update([
                'status' => MemberCoupon::STATUS_USED,
                'used_at' => now(),
            ]);

            try {
                app(MemberBenefitService::class)->deleteMemberCoupon($member, (int) $issued->id);
                $this->fail('Revocation must reject a concurrently used coupon.');
            } catch (\RuntimeException $exception) {
                $this->assertSame('使用済みクーポンは削除できません', $exception->getMessage());
            }
        } finally {
            DB::rollBack();
            DB::purge('revocation_writer');
        }
        $this->assertSame(MemberCoupon::STATUS_USED, $issued->fresh()->status);
    }
}
