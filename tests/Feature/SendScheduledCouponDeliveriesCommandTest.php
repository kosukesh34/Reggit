<?php

namespace Tests\Feature;

use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Services\Admin\MemberBenefitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public static function permanentFailures(): array
    {
        return [['expired'], ['invalid_segment']];
    }

    #[DataProvider('permanentFailures')]
    public function test_failed_delivery_does_not_block_later_deliveries(string $failure): void
    {
        $project = $this->createProject();
        Member::factory()->create(['project_id' => $project->id]);
        $isExpired = $failure === 'expired';
        $expired = Coupon::factory()->create(['project_id' => $project->id, 'end_at' => $isExpired ? now()->subDay() : null]);
        $valid = Coupon::factory()->create(['project_id' => $project->id, 'end_at' => null]);
        foreach ([$expired, $valid] as $index => $coupon) {
            CouponDelivery::create([
                'project_id' => $project->id,
                'coupon_id' => $coupon->id,
                'delivery_type' => $index === 0 && ! $isExpired
                    ? CouponDeliveryType::SCHEDULED_SEGMENT_ATTRIBUTES : CouponDeliveryType::SCHEDULED_ALL,
                'status' => CouponDeliveryStatus::SCHEDULED,
                'scheduled_at' => now()->subMinutes(2 - $index),
            ]);
        }

        $this->artisan('coupons:send-scheduled')->assertExitCode(1);

        $this->assertDatabaseHas('coupon_deliveries', [
            'coupon_id' => $expired->id,
            'status' => CouponDeliveryStatus::FAILED,
            'error_message' => $isExpired ? 'このクーポンは期限切れです' : '属性絞り込みでは1つ以上の条件を指定してください',
        ]);
        $this->assertDatabaseHas('coupon_deliveries', [
            'coupon_id' => $valid->id,
            'status' => CouponDeliveryStatus::SENT,
            'issued_count' => 1,
        ]);
        $this->assertSame(0, MemberCoupon::query()->where('coupon_id', $expired->id)->count());
        $this->assertSame(1, MemberCoupon::query()->where('coupon_id', $valid->id)->count());
        $this->artisan('coupons:send-scheduled')->assertSuccessful();
        $this->assertSame(1, MemberCoupon::query()->where('coupon_id', $valid->id)->count());
    }

    public function test_transient_failure_keeps_reservation_and_retries_without_blocking_other_deliveries(): void
    {
        $project = $this->createProject();
        Member::factory()->create(['project_id' => $project->id]);
        $coupons = Coupon::factory()->count(2)->create(['project_id' => $project->id, 'end_at' => null]);
        foreach ($coupons as $index => $coupon) {
            CouponDelivery::create([
                'project_id' => $project->id,
                'coupon_id' => $coupon->id,
                'delivery_type' => CouponDeliveryType::SCHEDULED_ALL,
                'status' => CouponDeliveryStatus::SCHEDULED,
                'scheduled_at' => now()->subMinutes(2 - $index),
            ]);
        }
        $service = app(MemberBenefitService::class);
        $failed = false;
        $mock = Mockery::mock(MemberBenefitService::class);
        $mock->shouldReceive('issueCouponToMembers')->times(3)->andReturnUsing(
            function (int $projectId, int $couponId, array $memberIds, ?string $sourceType, ?string $sourceId, bool $notify) use ($service, $coupons, &$failed): array {
                if ($couponId === $coupons[0]->id && ! $failed) {
                    $failed = true;
                    throw new \RuntimeException('Temporary database failure');
                }

                return $service->issueCouponToMembers($projectId, $couponId, $memberIds, $sourceType, $sourceId, $notify);
            },
        );
        app()->instance(MemberBenefitService::class, $mock);

        $this->artisan('coupons:send-scheduled')->assertExitCode(1);
        $this->assertDatabaseHas('coupon_deliveries', [
            'coupon_id' => $coupons[0]->id, 'status' => CouponDeliveryStatus::SCHEDULED,
        ]);
        $this->assertDatabaseHas('coupon_deliveries', [
            'coupon_id' => $coupons[1]->id, 'status' => CouponDeliveryStatus::SENT,
        ]);
        $this->assertSame(1, MemberCoupon::query()->where('coupon_id', $coupons[1]->id)->count());

        $this->artisan('coupons:send-scheduled')->assertSuccessful();
        $this->assertDatabaseHas('coupon_deliveries', [
            'coupon_id' => $coupons[0]->id, 'status' => CouponDeliveryStatus::SENT,
        ]);
        $this->assertSame(1, MemberCoupon::query()->where('coupon_id', $coupons[0]->id)->count());
        $this->assertSame(1, MemberCoupon::query()->where('coupon_id', $coupons[1]->id)->count());
    }
}
