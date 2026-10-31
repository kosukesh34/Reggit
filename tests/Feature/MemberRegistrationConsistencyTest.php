<?php

namespace Tests\Feature;

use App\Enums\ScheduledDeliveryType;
use App\Infrastructure\Persistence\Eloquent\Settings\MemberCodeSettingStore;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AfterRegistrationDelivery;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\CouponNotificationService;
use App\Services\Member\MemberRegistrar;
use App\Services\ScheduledDeliverySendService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MemberRegistrationConsistencyTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $type = 'sequential'): User
    {
        config(['smaregi.contract_id' => '']);
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setValue('member_code_type', $type, 'string', $project->id);
        ShopSettingStore::setValue('member_code_length', 1, 'integer', $project->id);

        return User::factory()->create(['project_id' => $project->id]);
    }

    public function test_sequential_code_exhaustion_does_not_wrap_or_advance_counter(): void
    {
        $user = $this->user();
        MemberCodeSettingStore::setMemberCodeNextNumber(10, $user->project_id);
        try {
            app(MemberRegistrar::class)->registerMember($user, ['name' => 'Test']);
            $this->fail('Expected exhausted code to fail');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('member_number', $exception->errors());
        }
        $this->assertDatabaseMissing('members', ['user_id' => $user->id]);
        $this->assertSame(10, MemberCodeSettingStore::getMemberCodeNextNumber($user->project_id));
    }

    public function test_soft_deleted_codes_remain_reserved_by_the_unique_constraint(): void
    {
        $user = $this->user();
        $old = Member::factory()->create(['project_id' => $user->project_id, 'member_number' => '1']);
        $old->delete();
        $member = app(MemberRegistrar::class)->registerMember($user, ['name' => 'Test']);
        $this->assertSame('2', $member->member_number);
    }

    public function test_random_code_exhaustion_never_returns_an_unchecked_duplicate(): void
    {
        $user = $this->user('random');
        foreach (range(0, 9) as $number) {
            Member::factory()->create(['project_id' => $user->project_id, 'member_number' => (string) $number]);
        }
        $this->expectException(ValidationException::class);
        app(MemberRegistrar::class)->registerMember($user, ['name' => 'Test']);
    }

    public function test_rolled_back_coupon_issue_never_notifies_the_member(): void
    {
        $user = $this->user();
        Coupon::factory()->create([
            'project_id' => $user->project_id, 'is_active' => true,
            'issue_trigger' => Coupon::ISSUE_TRIGGER_FIRST_REGISTRATION,
        ]);
        $notifications = \Mockery::mock(CouponNotificationService::class);
        $notifications->shouldNotReceive('notifyIssued');
        $this->app->instance(CouponNotificationService::class, $notifications);
        Event::listen('eloquent.updating: '.User::class, function (): void {
            throw new \RuntimeException('profile write failed');
        });
        try {
            app(MemberRegistrar::class)->registerMember($user, ['name' => 'Changed']);
            $this->fail('Expected registration to fail');
        } catch (\RuntimeException $exception) {
            $this->assertSame('profile write failed', $exception->getMessage());
        }
        $this->assertDatabaseCount('member_coupons', 0);
    }

    public function test_registration_rollback_never_runs_the_delivery_job(): void
    {
        $user = $this->user();
        AfterRegistrationDelivery::create([
            'project_id' => $user->project_id, 'schedule_type' => ScheduledDeliveryType::NEW_USER_MINUTES,
            'delay_minutes' => 0, 'title' => 'Welcome', 'is_active' => true,
        ]);
        $delivery = \Mockery::mock(ScheduledDeliverySendService::class);
        $delivery->shouldNotReceive('sendToMember');
        $this->app->instance(ScheduledDeliverySendService::class, $delivery);
        Event::listen('eloquent.updating: '.User::class, function (): void {
            throw new \RuntimeException('profile write failed');
        });
        try {
            app(MemberRegistrar::class)->registerMember($user, ['name' => 'Changed']);
            $this->fail('Expected registration to fail');
        } catch (\RuntimeException $exception) {
            $this->assertSame('profile write failed', $exception->getMessage());
        }
        $this->assertDatabaseMissing('members', ['user_id' => $user->id]);
        $this->assertSame(1, DB::transactionLevel());
    }
}
