<?php

namespace Tests\Feature;

use App\Domain\Order\Exceptions\OrderOperationException;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Order;
use App\Models\Project;
use App\Models\StampCard;
use App\Models\User;
use App\Services\Lottery\LotteryEntitlementService;
use App\Services\MemberBenefitService;
use App\Services\OrderCancellationReverser;
use App\Services\OrderRewardService;
use App\Services\Stamp\MemberCurrentStampCardResolver;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderMemberOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_owner_order_records_visit(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $order = Order::create(['project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 100, 'status' => 'pending']);

        app(OrderRewardService::class)->recordMemberVisit($user, $order);

        $this->assertDatabaseHas('member_visits', ['member_id' => $member->id, 'order_id' => $order->id]);
    }

    public function test_transfer_before_member_lock_prevents_order_visit(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $order = Order::create(['project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 100, 'status' => 'pending']);
        $newOwner = User::factory()->create(['project_id' => $project->id]);
        $changed = false;
        DB::connection()->beforeExecuting(function (string $sql) use ($member, $newOwner, &$changed): void {
            if (! $changed && str_contains($sql, 'from `members`') && str_contains($sql, 'for update')) {
                $changed = true;
                $member->update(['user_id' => $newOwner->id]);
            }
        });

        app(OrderRewardService::class)->recordMemberVisit($user, $order);

        $this->assertTrue($changed);
        $this->assertDatabaseMissing('member_visits', ['member_id' => $member->id, 'order_id' => $order->id]);
    }

    public function test_deleted_cached_member_does_not_receive_order_visit(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $user->load('member');
        $order = Order::create(['project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 100, 'status' => 'pending']);
        $member->delete();

        app(OrderRewardService::class)->recordMemberVisit($user, $order);

        $this->assertDatabaseMissing('member_visits', ['member_id' => $member->id, 'order_id' => $order->id]);
    }

    public function test_previous_owner_order_does_not_record_visit_for_transferred_member(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $user->load('member');
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setValue('use_stamps', true, 'boolean', (int) $project->id);
        ShopSettingStore::setValue('stamp_earn_per_amount', 100, 'integer', (int) $project->id);
        StampCard::factory()->create(['project_id' => $project->id]);
        $this->assertTrue(ShopSettingStore::getUseStamps((int) $project->id));
        $this->assertNotNull(app(MemberCurrentStampCardResolver::class)->resolve($member));
        $order = Order::create(['project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 100, 'status' => 'pending']);
        $newOwner = User::factory()->create(['project_id' => $project->id]);
        $member->update(['user_id' => $newOwner->id]);

        $this->mock(MemberBenefitService::class, function ($mock): void {
            $mock->shouldReceive('addStampsToMember')->never();
        });
        $this->mock(LotteryEntitlementService::class, function ($mock): void {
            $mock->shouldReceive('grantForPurchase')->never();
        });
        $rewards = app(OrderRewardService::class);
        $rewards->recordMemberVisit($user, $order);
        $rewards->awardStamps($user, $order, 1000);
        $rewards->awardLotteryEntitlements($user, $order, 1000);

        $this->assertDatabaseMissing('member_visits', ['member_id' => $member->id, 'order_id' => $order->id]);
    }

    public function test_transfer_before_point_lock_rejects_consumption_without_changing_ledger(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'points' => 100]);
        $order = Order::create(['project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 100, 'status' => 'pending']);
        $newOwner = User::factory()->create(['project_id' => $project->id]);
        $changed = false;
        DB::connection()->beforeExecuting(function (string $sql) use ($member, $newOwner, &$changed): void {
            if (! $changed && str_contains($sql, 'from `members`') && str_contains($sql, 'for update')) {
                $changed = true;
                $member->update(['user_id' => $newOwner->id]);
            }
        });
        $rejected = false;
        DB::transaction(function () use ($user, $order, &$rejected): void {
            try {
                app(OrderRewardService::class)->consumePointsForOrder($user, $order, 30);
            } catch (OrderOperationException) {
                $rejected = true;
            }
        });

        $this->assertTrue($changed);
        $this->assertTrue($rejected);
        $this->assertSame(100, $member->fresh()->points);
        $this->assertDatabaseMissing('point_transactions', ['member_id' => $member->id]);
    }

    public function test_transfer_before_cancellation_point_lock_does_not_refund_another_owner(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'points' => 0]);
        $order = Order::create(['project_id' => $project->id, 'user_id' => $user->id, 'total_amount' => 100, 'status' => 'cancelled', 'points_used' => 30, 'points_earned' => 0, 'payment_method' => 'stripe']);
        $newOwner = User::factory()->create(['project_id' => $project->id]);
        $changed = false;
        DB::connection()->beforeExecuting(function (string $sql) use ($member, $newOwner, &$changed): void {
            if (! $changed && str_contains($sql, 'from `members`') && str_contains($sql, 'for update')) {
                $changed = true;
                $member->update(['user_id' => $newOwner->id]);
            }
        });

        app(OrderCancellationReverser::class)->handleOrderCancellation($order);

        $this->assertTrue($changed);
        $this->assertSame(0, $member->fresh()->points);
        $this->assertDatabaseMissing('point_transactions', ['order_id' => $order->id]);
    }
}
