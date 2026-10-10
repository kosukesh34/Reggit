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

class MemberCouponDeletionTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_deletion_sees_redemption_committed_after_an_outer_snapshot(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL repeatable-read snapshots.');
        }
        $project = Project::factory()->create();
        $member = Member::factory()->create(['project_id' => $project->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $issued = MemberCoupon::factory()->create([
            'project_id' => $project->id, 'member_id' => $member->id,
            'coupon_id' => $coupon->id, 'status' => MemberCoupon::STATUS_UNUSED,
        ]);
        config(['database.connections.coupon_writer' => config('database.connections.'.DB::getDefaultConnection())]);
        $writer = DB::connection('coupon_writer');
        DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        DB::beginTransaction();
        try {
            $this->assertTrue(MemberCoupon::findOrFail($issued->id)->isUnused());
            $writer->table('member_coupons')->where('id', $issued->id)->update([
                'status' => MemberCoupon::STATUS_USED, 'used_at' => now(),
            ]);
            $redemptionId = $writer->table('coupon_redemptions')->insertGetId([
                'project_id' => $project->id, 'coupon_id' => $coupon->id,
                'member_id' => $member->id, 'member_coupon_id' => $issued->id,
                'used_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->assertTrue(MemberCoupon::findOrFail($issued->id)->isUnused(), 'The outer transaction still has its old snapshot.');
            $rejected = false;
            try {
                app(MemberBenefitService::class)->deleteMemberCoupon($member, (int) $issued->id);
            } catch (\RuntimeException $exception) {
                $rejected = true;
                $this->assertSame('使用済みクーポンは削除できません', $exception->getMessage());
            }
            $this->assertTrue($rejected, 'A redeemed coupon must not be deleted.');
            DB::commit();
            $this->assertDatabaseHas('member_coupons', ['id' => $issued->id, 'status' => MemberCoupon::STATUS_USED]);
            $this->assertDatabaseHas('coupon_redemptions', ['id' => $redemptionId, 'member_coupon_id' => $issued->id]);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('coupon_writer');
        }
    }
}
