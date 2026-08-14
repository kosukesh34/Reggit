<?php

namespace Tests\Feature;

use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendScheduledCouponDeliveriesCommandTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_到達した予約配信を実行すること(): void
    {
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id]);
        CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::SCHEDULED_ALL,
            'status' => CouponDeliveryStatus::SCHEDULED,
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('coupons:send-scheduled')->assertSuccessful();

        $this->assertDatabaseHas('coupon_deliveries', [
            'coupon_id' => $coupon->id,
            'status' => CouponDeliveryStatus::SENT,
        ]);
        $this->assertSame(1, MemberCoupon::query()->where('member_id', $member->id)->where('coupon_id', $coupon->id)->count());
    }

    public function test_未来の予約配信は実行しないこと(): void
    {
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id]);
        CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::SCHEDULED_ALL,
            'status' => CouponDeliveryStatus::SCHEDULED,
            'scheduled_at' => now()->addHour(),
        ]);

        $this->artisan('coupons:send-scheduled')->assertSuccessful();

        $this->assertDatabaseHas('coupon_deliveries', [
            'coupon_id' => $coupon->id,
            'status' => CouponDeliveryStatus::SCHEDULED,
        ]);
        $this->assertSame(0, MemberCoupon::query()->where('coupon_id', $coupon->id)->count());
    }

    public function test_dry_runは発行しないこと(): void
    {
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id]);
        CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::SCHEDULED_ALL,
            'status' => CouponDeliveryStatus::SCHEDULED,
            'scheduled_at' => now()->subMinute(),
        ]);

        $this->artisan('coupons:send-scheduled', ['--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseHas('coupon_deliveries', [
            'coupon_id' => $coupon->id,
            'status' => CouponDeliveryStatus::SCHEDULED,
        ]);
        $this->assertSame(0, MemberCoupon::query()->where('coupon_id', $coupon->id)->count());
    }
}
