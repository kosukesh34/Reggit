<?php

namespace Tests\Feature;

use App\Enums\ScheduledDeliveryContentType;
use App\Enums\ScheduledDeliveryType;
use App\Models\AfterRegistrationDelivery;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Models\ScheduledDeliveryAttempt;
use App\Services\Admin\MemberBenefitService;
use App\Services\ScheduledDeliveryTargetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScheduledDeliveryRetryTest extends TestCase
{
    use RefreshDatabase;

    private function delivery(): AfterRegistrationDelivery
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(23, 59));
        $project = Project::factory()->create();
        Member::factory()->create(['project_id' => $project->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'end_at' => null]);

        return AfterRegistrationDelivery::create([
            'project_id' => $project->id, 'coupon_id' => $coupon->id,
            'schedule_type' => ScheduledDeliveryType::MONTHLY,
            'content_type' => ScheduledDeliveryContentType::COUPON_ONLY,
            'send_notification' => false, 'push_hour' => 23, 'push_minute' => 59,
            'params' => ['day_of_month' => 1], 'is_active' => true,
        ]);
    }

    public function test_retry_next_day_keeps_original_content_recipients_and_occurrence(): void
    {
        $delivery = $this->delivery();
        $couponId = (int) $delivery->coupon_id;
        $member = Member::where('project_id', $delivery->project_id)->firstOrFail();
        $benefits = app(MemberBenefitService::class);
        $calls = 0;
        $this->mock(MemberBenefitService::class, function ($mock) use ($benefits, &$calls): void {
            $mock->shouldReceive('issueCouponToMember')->twice()->andReturnUsing(function (...$arguments) use ($benefits, &$calls): MemberCoupon {
                if (++$calls === 1) {
                    throw new \RuntimeException('Temporary grant failure');
                }

                return $benefits->issueCouponToMember(...$arguments);
            });
        });
        $this->artisan('deliveries:send-scheduled')->assertExitCode(1);
        $replacement = Coupon::factory()->create(['project_id' => $delivery->project_id, 'end_at' => null]);
        $delivery->update(['coupon_id' => $replacement->id]);
        Member::factory()->create(['project_id' => $delivery->project_id]);
        $this->travel(1)->minutes();
        $this->artisan('deliveries:send-scheduled')->assertSuccessful();
        $this->assertDatabaseCount('member_coupons', 1);
        $this->assertDatabaseHas('member_coupons', ['member_id' => $member->id, 'coupon_id' => $couponId]);
        $this->assertDatabaseHas('scheduled_delivery_sends', [
            'after_registration_delivery_id' => $delivery->id,
            'member_id' => $member->id, 'send_key' => '2026-10-01', 'sent_at' => now()->toDateTimeString(),
        ]);
        $this->assertDatabaseCount('scheduled_delivery_attempts', 0);
    }

    public function test_target_selection_is_retried_after_the_scheduled_minute(): void
    {
        $delivery = $this->delivery();
        $member = Member::where('project_id', $delivery->project_id)->firstOrFail();
        $calls = 0;
        $this->mock(ScheduledDeliveryTargetService::class, function ($mock) use ($member, &$calls): void {
            $mock->shouldReceive('memberIds')->twice()->andReturnUsing(function ($delivery, $date) use ($member, &$calls): array {
                $this->assertSame('2026-10-01', $date->toDateString());
                if (++$calls === 1) {
                    throw new \RuntimeException('Temporary target query failure');
                }

                return [(int) $member->id];
            });
        });
        $this->artisan('deliveries:send-scheduled')->assertExitCode(1);
        $this->travel(1)->minutes();
        $this->artisan('deliveries:send-scheduled')->assertSuccessful();
        $this->assertDatabaseCount('member_coupons', 1);
        $this->assertDatabaseCount('scheduled_delivery_attempts', 0);
    }

    public function test_expired_claim_recovers_and_old_worker_cannot_acknowledge_new_claim(): void
    {
        $delivery = $this->delivery();
        $member = Member::where('project_id', $delivery->project_id)->firstOrFail();
        $attempt = ScheduledDeliveryAttempt::create([
            'after_registration_delivery_id' => $delivery->id, 'occurrence_key' => now()->toDateString(),
            'scheduled_at' => now(), 'delivery_snapshot' => $delivery->getAttributes(),
            'target_member_ids' => [(int) $member->id], 'attempts' => 1, 'next_attempt_at' => now()->addMinutes(5),
        ]);
        $this->travel(1)->minutes();
        $this->artisan('deliveries:send-scheduled')->assertSuccessful();
        $this->assertDatabaseCount('member_coupons', 0);
        $benefits = app(MemberBenefitService::class);
        $this->mock(MemberBenefitService::class, function ($mock) use ($attempt, $benefits): void {
            $mock->shouldReceive('issueCouponToMember')->once()->andReturnUsing(function (...$arguments) use ($attempt, $benefits): MemberCoupon {
                $coupon = $benefits->issueCouponToMember(...$arguments);
                $attempt->update(['attempts' => 3, 'next_attempt_at' => now()->addMinutes(5)]);

                return $coupon;
            });
        });
        $this->travel(4)->minutes();
        $this->artisan('deliveries:send-scheduled')->assertSuccessful();
        $this->assertDatabaseHas('scheduled_delivery_attempts', ['id' => $attempt->id, 'attempts' => 3, 'failed_at' => null]);
        $this->travel(5)->minutes();
        $this->artisan('deliveries:send-scheduled')->assertSuccessful();
        $this->assertDatabaseCount('scheduled_delivery_attempts', 0);
        $this->assertDatabaseCount('member_coupons', 1);
    }

    public function test_retries_are_bounded_and_disabling_a_delivery_cancels_pending_work(): void
    {
        $delivery = $this->delivery();
        $delivery->coupon->update(['end_at' => now()->subDay()]);
        $this->artisan('deliveries:send-scheduled')->assertExitCode(1);
        foreach ([1, 5, 15, 60] as $minutes) {
            $this->travel($minutes)->minutes();
            $this->artisan('deliveries:send-scheduled')->assertExitCode(1);
        }
        $attempt = ScheduledDeliveryAttempt::firstOrFail();
        $this->assertSame(5, $attempt->attempts);
        $this->assertNotNull($attempt->failed_at);
        $this->travel(1)->days();
        $this->artisan('deliveries:send-scheduled')->assertSuccessful();
        $this->assertSame(5, $attempt->fresh()->attempts);
        $attempt->update(['attempts' => 1, 'failed_at' => null, 'next_attempt_at' => now()]);
        $delivery->update(['is_active' => false]);
        $this->artisan('deliveries:send-scheduled')->assertSuccessful();
        $this->assertDatabaseCount('scheduled_delivery_attempts', 0);
        $this->assertDatabaseCount('member_coupons', 0);
    }

    public function test_an_old_once_delivery_attempt_does_not_block_the_next_days_members(): void
    {
        foreach ([false, true] as $terminal) {
            $delivery = $this->delivery();
            $delivery->update(['schedule_type' => ScheduledDeliveryType::NEW_USER_DAYS, 'params' => ['delay_days' => 1]]);
            $oldMember = Member::where('project_id', $delivery->project_id)->firstOrFail();
            $oldMember->forceFill(['created_at' => now()->subDay()])->save();
            ScheduledDeliveryAttempt::create([
                'after_registration_delivery_id' => $delivery->id, 'occurrence_key' => now()->toDateString(),
                'scheduled_at' => now(), 'delivery_snapshot' => $delivery->getAttributes(),
                'target_member_ids' => [(int) $oldMember->id], 'attempts' => $terminal ? 5 : 1,
                'next_attempt_at' => now()->addDays(2), 'failed_at' => $terminal ? now() : null,
            ]);
            $newMember = Member::factory()->create(['project_id' => $delivery->project_id, 'created_at' => now()]);
            $this->travel(1)->days();
            $this->artisan('deliveries:send-scheduled')->assertSuccessful();
            $this->assertDatabaseHas('member_coupons', ['member_id' => $newMember->id, 'coupon_id' => $delivery->coupon_id]);
            $this->assertDatabaseMissing('member_coupons', ['member_id' => $oldMember->id]);
            $delivery->update(['is_active' => false]);
        }
    }

    public function test_last_visit_retry_selects_members_relative_to_the_original_date(): void
    {
        $delivery = $this->delivery();
        $delivery->update(['schedule_type' => ScheduledDeliveryType::LAST_VISIT, 'params' => ['delay_days' => 7]]);
        $member = Member::where('project_id', $delivery->project_id)->firstOrFail();
        $member->update(['last_visited_at' => now()->subDays(7)]);
        $decoy = Member::factory()->create(['project_id' => $delivery->project_id, 'last_visited_at' => now()->subDays(6)]);
        $targets = app(ScheduledDeliveryTargetService::class);
        $calls = 0;
        $this->mock(ScheduledDeliveryTargetService::class, function ($mock) use ($targets, &$calls): void {
            $mock->shouldReceive('memberIds')->twice()->andReturnUsing(function ($delivery, $date) use ($targets, &$calls): array {
                if (++$calls === 1) {
                    throw new \RuntimeException('Temporary target query failure');
                }

                return $targets->memberIds($delivery, $date);
            });
        });
        $this->artisan('deliveries:send-scheduled')->assertExitCode(1);
        $this->travel(1)->minutes();
        $this->artisan('deliveries:send-scheduled')->assertSuccessful();
        $this->assertDatabaseHas('member_coupons', ['member_id' => $member->id]);
        $this->assertDatabaseMissing('member_coupons', ['member_id' => $decoy->id]);
    }

    public function test_acknowledgement_failure_does_not_stop_later_deliveries(): void
    {
        $delivery = $this->delivery();
        $second = $delivery->replicate();
        $second->save();
        $failed = false;
        DB::connection()->beforeExecuting(function ($query) use (&$failed): void {
            if (! $failed && str_starts_with($query, 'delete from `scheduled_delivery_attempts`')) {
                $failed = true;
                throw new \RuntimeException('Temporary acknowledgement failure');
            }
        });
        $this->artisan('deliveries:send-scheduled')->assertExitCode(1);
        $this->assertTrue($failed);
        $this->assertDatabaseCount('member_coupons', 2);
        $this->assertDatabaseCount('scheduled_delivery_attempts', 1);
        $this->travel(5)->minutes();
        $this->artisan('deliveries:send-scheduled')->assertSuccessful();
        $this->assertDatabaseCount('member_coupons', 2);
        $this->assertDatabaseCount('scheduled_delivery_attempts', 0);
    }
}
