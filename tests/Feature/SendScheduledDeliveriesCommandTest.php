<?php

namespace Tests\Feature;

use App\Enums\ScheduledDeliveryContentType;
use App\Enums\ScheduledDeliveryType;
use App\Models\AfterRegistrationDelivery;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Services\Admin\MemberBenefitService;
use App\Services\ScheduledDeliveryTargetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SendScheduledDeliveriesCommandTest extends TestCase
{
    use RefreshDatabase;

    private function delivery(Project $project, Coupon $coupon): AfterRegistrationDelivery
    {
        return AfterRegistrationDelivery::create([
            'project_id' => $project->id,
            'schedule_type' => ScheduledDeliveryType::MONTHLY,
            'content_type' => ScheduledDeliveryContentType::COUPON_ONLY,
            'coupon_id' => $coupon->id,
            'send_notification' => false,
            'push_hour' => 10,
            'push_minute' => 0,
            'params' => ['day_of_month' => 1],
            'is_active' => true,
        ]);
    }

    public function test_expired_coupon_does_not_block_another_projects_delivery(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(10, 0));
        $projects = Project::factory()->count(2)->create();
        $members = [];
        $deliveries = [];
        foreach ($projects as $index => $project) {
            $members[] = Member::factory()->create(['project_id' => $project->id]);
            $coupon = Coupon::factory()->create([
                'project_id' => $project->id,
                'end_at' => $index === 0 ? now()->subDay() : null,
            ]);
            $deliveries[] = $this->delivery($project, $coupon);
        }

        $this->artisan('deliveries:send-scheduled')->assertExitCode(1);

        $this->assertDatabaseMissing('scheduled_delivery_sends', ['after_registration_delivery_id' => $deliveries[0]->id]);
        $this->assertDatabaseMissing('member_coupons', ['member_id' => $members[0]->id]);
        $this->assertDatabaseHas('scheduled_delivery_sends', ['after_registration_delivery_id' => $deliveries[1]->id]);
        $this->assertSame(1, MemberCoupon::where('member_id', $members[1]->id)->count());
    }

    public function test_member_failure_does_not_block_later_members_and_can_retry_without_duplicates(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(10, 0));
        $project = Project::factory()->create();
        $members = Member::factory()->count(2)->create(['project_id' => $project->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'end_at' => null]);
        $delivery = $this->delivery($project, $coupon);
        $this->mock(ScheduledDeliveryTargetService::class, function ($mock) use ($members): void {
            $mock->shouldReceive('memberIds')->twice()->andReturn($members->modelKeys());
        });
        $benefits = app(MemberBenefitService::class);
        $failed = false;
        $mock = Mockery::mock(MemberBenefitService::class);
        $mock->shouldReceive('issueCouponToMember')->times(3)->andReturnUsing(
            function (Member $member, ...$arguments) use ($members, $benefits, &$failed): MemberCoupon {
                if ($member->id === $members[0]->id && ! $failed) {
                    $failed = true;
                    throw new \RuntimeException('Temporary grant failure');
                }

                return $benefits->issueCouponToMember($member, ...$arguments);
            },
        );
        app()->instance(MemberBenefitService::class, $mock);

        $this->artisan('deliveries:send-scheduled')->assertExitCode(1);
        $this->assertDatabaseMissing('scheduled_delivery_sends', [
            'after_registration_delivery_id' => $delivery->id, 'member_id' => $members[0]->id,
        ]);
        $this->assertDatabaseHas('scheduled_delivery_sends', [
            'after_registration_delivery_id' => $delivery->id, 'member_id' => $members[1]->id,
        ]);

        $this->artisan('deliveries:send-scheduled')->assertSuccessful();
        $this->assertDatabaseCount('scheduled_delivery_sends', 2);
        foreach ($members as $member) {
            $this->assertSame(1, MemberCoupon::where('member_id', $member->id)->where('coupon_id', $coupon->id)->count());
        }
    }

    public function test_target_selection_failure_does_not_block_later_delivery(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 1)->setTime(10, 0));
        $project = Project::factory()->create();
        $member = Member::factory()->create(['project_id' => $project->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'end_at' => null]);
        $failed = $this->delivery($project, $coupon);
        $valid = $this->delivery($project, $coupon);
        $this->mock(ScheduledDeliveryTargetService::class, function ($mock) use ($failed, $member): void {
            $mock->shouldReceive('memberIds')->twice()->andReturnUsing(
                function (AfterRegistrationDelivery $delivery) use ($failed, $member): array {
                    if ($delivery->id === $failed->id) {
                        throw new \RuntimeException('Target query failed');
                    }

                    return [$member->id];
                },
            );
        });

        $this->artisan('deliveries:send-scheduled')->assertExitCode(1);
        $this->assertDatabaseMissing('scheduled_delivery_sends', ['after_registration_delivery_id' => $failed->id]);
        $this->assertDatabaseHas('scheduled_delivery_sends', ['after_registration_delivery_id' => $valid->id]);
    }
}
