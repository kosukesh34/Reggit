<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Coupon\EloquentCouponDeliveryTargetRepository;
use App\Models\Member;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\User;
use App\Models\UserDevice;
use App\Services\Admin\CouponAdminService;
use App\Services\Admin\MemberSegmentService;
use App\Services\Smaregi\SmaregiCouponSyncService;
use App\Support\MemberChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\Support\GrantsMemberPoints;
use Tests\TestCase;

class MemberSegmentAndChannelTest extends TestCase
{
    use GrantsMemberPoints;
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function validLineUserId(string $suffix = 'a'): string
    {
        return 'U'.str_pad(substr(md5($suffix), 0, 32), 32, '0');
    }

    public function test_member_channel_正規LINEとゲストを判定できること(): void
    {
        $lineUser = new User(['line_user_id' => $this->validLineUserId('line')]);
        $guestUser = new User(['line_user_id' => 'guest-abc']);

        $this->assertTrue(MemberChannel::hasLine($lineUser));
        $this->assertFalse(MemberChannel::hasLine($guestUser));
        $this->assertSame('LINE', MemberChannel::label($lineUser));
        $this->assertSame('—', MemberChannel::label($guestUser));
    }

    public function test_repository_チャネルと来店回数と最終来店で絞り込めること(): void
    {
        $project = $this->createProject();
        $repo = new EloquentCouponDeliveryTargetRepository;

        $lineUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $this->validLineUserId('1'),
        ]);
        $lineMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $lineUser->id,
            'last_visited_at' => now()->subDays(5)->toDateString(),
        ]);

        $appUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'guest-app-user',
        ]);
        $appMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $appUser->id,
            'last_visited_at' => now()->subDays(40)->toDateString(),
        ]);
        UserDevice::create([
            'project_id' => $project->id,
            'user_id' => $appUser->id,
            'platform' => 'ios',
            'device_identifier' => 'device-1',
        ]);

        Order::create([
            'project_id' => $project->id,
            'user_id' => $lineUser->id,
            'order_number' => 'ORD-1',
            'order_type' => 'takeout',
            'total_amount' => 1000,
            'status' => 'completed',
        ]);
        Order::create([
            'project_id' => $project->id,
            'user_id' => $lineUser->id,
            'order_number' => 'ORD-2',
            'order_type' => 'takeout',
            'total_amount' => 2000,
            'status' => 'completed',
        ]);

        $lineIds = $repo->findIdsByChannel($project->id, MemberChannel::LINE);
        $appIds = $repo->findIdsByChannel($project->id, MemberChannel::APP);
        $this->assertSame([$lineMember->id], $lineIds);
        $this->assertSame([$appMember->id], $appIds);

        $visitIds = $repo->findIdsByVisitCount($project->id, 2, null);
        $this->assertSame([$lineMember->id], $visitIds);

        $inactiveIds = $repo->findIdsByLastVisited($project->id, MemberSegmentService::LAST_VISIT_INACTIVE, 30);
        $this->assertContains($appMember->id, $inactiveIds);
        $this->assertNotContains($lineMember->id, $inactiveIds);

        $recentIds = $repo->findIdsByLastVisited($project->id, MemberSegmentService::LAST_VISIT_WITHIN, 30);
        $this->assertContains($lineMember->id, $recentIds);
        $this->assertNotContains($appMember->id, $recentIds);
    }

    public function test_member_segment_service_AND交差できること(): void
    {
        $project = $this->createProject();
        $service = app(MemberSegmentService::class);

        $lineUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $this->validLineUserId('and'),
        ]);
        $member = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $lineUser->id,
            'birthday' => '1990-07-15',
            'sex' => '1',
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create([
                'project_id' => $project->id,
                'line_user_id' => $this->validLineUserId('other'),
            ])->id,
            'birthday' => '1990-01-01',
            'sex' => '2',
        ]);

        $ids = $service->getMemberIds($project->id, [
            'channel' => MemberChannel::LINE,
            'birthday_month' => 7,
            'sex' => '1',
        ]);

        $this->assertSame([$member->id], $ids->all());
    }

    public function test_lifetime_granted_points_付与で累計が増え絞り込みできること(): void
    {
        $project = $this->createProject();
        $memberHigh = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'lifetime_granted_points' => 0,
        ]);
        $memberLow = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'lifetime_granted_points' => 0,
        ]);

        $this->grantMemberPoints($memberHigh, 1500);
        $this->grantMemberPoints($memberLow, 200);

        $memberHigh->refresh();
        $memberLow->refresh();
        $this->assertSame(1500, (int) $memberHigh->lifetime_granted_points);
        $this->assertSame(200, (int) $memberLow->lifetime_granted_points);

        PointTransaction::create([
            'project_id' => $project->id,
            'member_id' => $memberHigh->id,
            'type' => 'reversed',
            'points' => -100,
            'description' => '取消テスト',
        ]);
        $memberHigh->refresh();
        $this->assertSame(1400, (int) $memberHigh->lifetime_granted_points);

        $repo = new EloquentCouponDeliveryTargetRepository;
        $ids = $repo->findIdsByLifetimeGrantedPoints($project->id, 1000, null);
        $this->assertSame([$memberHigh->id], $ids);

        $service = app(MemberSegmentService::class);
        $segmentIds = $service->getMemberIds($project->id, [
            'lifetime_points_min' => 1000,
            'lifetime_points_max' => 2000,
        ]);
        $this->assertSame([$memberHigh->id], $segmentIds->all());
    }

    /** スマレジAPIはモックし、実POSには作成しない（ノイズ防止） */
    public function test_coupon_admin_create_スマレジ契約ありならPOS登録後に保存すること(): void
    {
        $project = $this->createProject();
        $sync = Mockery::mock(SmaregiCouponSyncService::class);
        $sync->shouldReceive('hasSmaregiContract')->once()->with($project->id)->andReturn(true);
        $sync->shouldReceive('registerToSmaregi')->once()->andReturn(['smaregi_coupon_id' => 'smaregi-99']);

        $service = new CouponAdminService($sync);
        $coupon = $service->create($project->id, [
            'code' => 'TESTCODE1',
            'name' => 'テストクーポン',
            'type' => 'fixed',
            'value' => 100,
            'is_active' => true,
        ]);

        $this->assertSame('smaregi-99', $coupon->smaregi_coupon_id);
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id,
            'smaregi_coupon_id' => 'smaregi-99',
        ]);
    }

    public function test_coupon_admin_create_スマレジ失敗時はDB保存しないこと(): void
    {
        $project = $this->createProject();
        $sync = Mockery::mock(SmaregiCouponSyncService::class);
        $sync->shouldReceive('hasSmaregiContract')->once()->andReturn(true);
        $sync->shouldReceive('registerToSmaregi')->once()->andReturn(null);

        $service = new CouponAdminService($sync);

        $this->expectException(\RuntimeException::class);
        try {
            $service->create($project->id, [
                'code' => 'FAILCODE1',
                'name' => '失敗クーポン',
                'type' => 'fixed',
                'value' => 100,
                'is_active' => true,
            ]);
        } finally {
            $this->assertDatabaseMissing('coupons', ['code' => 'FAILCODE1']);
        }
    }
}
