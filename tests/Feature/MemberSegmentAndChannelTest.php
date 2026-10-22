<?php

namespace Tests\Feature;

use App\Enums\MemberVisitSource;
use App\Enums\OrderStatus;
use App\Enums\SegmentCombineMode;
use App\Infrastructure\Persistence\Eloquent\Coupon\EloquentCouponDeliveryTargetRepository;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberAudienceGroup;
use App\Models\MemberCoupon;
use App\Models\MemberVisit;
use App\Models\MemberVisitItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointTransaction;
use App\Models\Product;
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

    public function test_member_channel_正規_lin_eとゲストを判定できること(): void
    {
        $lineUser = new User(['line_user_id' => $this->validLineUserId('line')]);
        $guestUser = new User(['line_user_id' => 'guest-abc']);

        $this->assertTrue(MemberChannel::hasLine($lineUser));
        $this->assertFalse(MemberChannel::hasLine($guestUser));
        $this->assertSame('LINE', MemberChannel::label($lineUser));
        $this->assertSame('—', MemberChannel::label($guestUser));
    }

    public function test_member_channel_labelがiosとandroidを分けて表示すること(): void
    {
        $project = $this->createProject();

        $iosUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'guest-ios',
        ]);
        UserDevice::create([
            'project_id' => $project->id,
            'user_id' => $iosUser->id,
            'platform' => 'ios',
            'device_identifier' => 'ios-1',
        ]);
        $this->assertSame('iOS', MemberChannel::label($iosUser->fresh('devices')));

        $androidUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'guest-android',
        ]);
        UserDevice::create([
            'project_id' => $project->id,
            'user_id' => $androidUser->id,
            'platform' => 'android',
            'device_identifier' => 'android-1',
        ]);
        $this->assertSame('Android', MemberChannel::label($androidUser->fresh('devices')));

        $bothUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'guest-both',
        ]);
        UserDevice::create([
            'project_id' => $project->id,
            'user_id' => $bothUser->id,
            'platform' => 'ios',
            'device_identifier' => 'both-ios',
        ]);
        UserDevice::create([
            'project_id' => $project->id,
            'user_id' => $bothUser->id,
            'platform' => 'android',
            'device_identifier' => 'both-android',
        ]);
        $this->assertSame('iOS・Android', MemberChannel::label($bothUser->fresh('devices')));

        $lineIosUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $this->validLineUserId('line-ios'),
        ]);
        UserDevice::create([
            'project_id' => $project->id,
            'user_id' => $lineIosUser->id,
            'platform' => 'ios',
            'device_identifier' => 'line-ios-1',
        ]);
        $this->assertSame('LINE・iOS', MemberChannel::label($lineIosUser->fresh('devices')));

        $webUser = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'guest-web',
        ]);
        UserDevice::create([
            'project_id' => $project->id,
            'user_id' => $webUser->id,
            'platform' => 'web',
            'device_identifier' => 'web-1',
        ]);
        $this->assertSame('アプリ', MemberChannel::label($webUser->fresh('devices')));
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

        $daysAfterIds = $repo->findIdsByLastVisited($project->id, MemberSegmentService::LAST_VISIT_DAYS_AFTER, 5);
        $this->assertContains($lineMember->id, $daysAfterIds);
        $this->assertNotContains($appMember->id, $daysAfterIds);
    }

    public function test_member_segment_service_an_d交差できること(): void
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

    public function test_member_segment_service_保存したグループを条件に追加できること(): void
    {
        $project = $this->createProject();
        $service = app(MemberSegmentService::class);
        $male = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'sex' => '1',
            'birthday' => '1990-07-15',
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'sex' => '2',
            'birthday' => '1990-07-15',
        ]);
        $group = MemberAudienceGroup::query()->create([
            'project_id' => $project->id,
            'name' => '男性',
            'criteria' => ['sex' => '1'],
        ]);

        $criteria = $service->buildCriteriaFromInput([
            'segment_birthday_months' => [7],
            'segment_audience_group_ids' => [$group->id],
        ]);

        $this->assertSame([$group->id], $criteria['audience_group_ids']);
        $this->assertSame([$male->id], $service->getMemberIds($project->id, $criteria)->all());
    }

    public function test_member_segment_service_項目ごとに_or結合できること(): void
    {
        $project = $this->createProject();
        $service = app(MemberSegmentService::class);
        $julyMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'birthday' => '1990-07-15',
            'sex' => '2',
        ]);
        $maleMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'birthday' => '1990-01-01',
            'sex' => '1',
        ]);

        $criteria = $service->buildCriteriaFromInput([
            'segment_birthday_months' => [7],
            'segment_sex' => '1',
            'segment_operators' => ['sex' => 'or'],
        ]);

        $this->assertNotNull($criteria);
        $this->assertEqualsCanonicalizing(
            [$julyMember->id, $maleMember->id],
            $service->getMemberIds($project->id, $criteria)->all(),
        );
    }

    public function test_member_segment_service_全体の一致方法で_and_orを切り替えられること(): void
    {
        $project = $this->createProject();
        $service = app(MemberSegmentService::class);
        $femaleJuly = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'birthday' => '1990-07-15', 'sex' => '2',
        ]);
        $maleJanuary = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'birthday' => '1990-01-01', 'sex' => '1',
        ]);
        $input = ['segment_birthday_months' => [7], 'segment_sex' => '1'];

        $all = $service->buildCriteriaFromInput($input + ['segment_match_mode' => 'all']);
        $any = $service->buildCriteriaFromInput($input + ['segment_match_mode' => 'any']);

        $this->assertSame([], $service->getMemberIds($project->id, $all)->all());
        $this->assertEqualsCanonicalizing([$femaleJuly->id, $maleJanuary->id], $service->getMemberIds($project->id, $any)->all());

        $multipleMonths = $service->buildCriteriaFromInput([
            'segment_birthday_months' => [1, 7],
            'segment_sex' => '2',
            'segment_match_mode' => 'all',
        ]);
        $this->assertSame([$femaleJuly->id], $service->getMemberIds($project->id, $multipleMonths)->all());
    }

    public function test_member_segment_service_プロフィール任意項目で絞り込めること(): void
    {
        $project = $this->createProject();
        $matching = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'profile_extra' => ['favorite_color' => '青'],
        ]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'profile_extra' => ['favorite_color' => '赤'],
        ]);
        $service = app(MemberSegmentService::class);
        $criteria = $service->buildCriteriaFromInput([
            'segment_profile' => ['favorite_color' => ['青']],
        ]);

        $this->assertNotNull($criteria);
        $this->assertSame([$matching->id], $service->getMemberIds($project->id, $criteria)->all());
    }

    public function test_member_segment_service_複数選択は_andとorを切り替えられること(): void
    {
        $project = $this->createProject();
        $service = app(MemberSegmentService::class);
        $both = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'profile_extra' => ['foods' => ['カレー', '焼肉']],
        ]);
        $curryOnly = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'profile_extra' => ['foods' => ['カレー']],
        ]);
        $input = ['segment_profile' => ['foods' => ['カレー', '焼肉']]];

        $or = $service->buildCriteriaFromInput($input + ['segment_value_mode' => ['profile' => ['foods' => SegmentCombineMode::ANY]]]);
        $and = $service->buildCriteriaFromInput($input + ['segment_value_mode' => ['profile' => ['foods' => SegmentCombineMode::ALL]]]);

        $this->assertEqualsCanonicalizing([$both->id, $curryOnly->id], $service->getMemberIds($project->id, $or)->all());
        $this->assertSame([$both->id], $service->getMemberIds($project->id, $and)->all());
    }

    public function test_member_segment_service_1人1つの属性はすべて指定でもいずれかで絞り込むこと(): void
    {
        $project = $this->createProject();
        $service = app(MemberSegmentService::class);
        $july = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'birthday' => '1990-07-15',
        ]);
        $january = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
            'birthday' => '1990-01-01',
        ]);

        $criteria = $service->buildCriteriaFromInput([
            'segment_birthday_months' => [1, 7],
            'segment_value_mode' => ['birthday_months' => SegmentCombineMode::ALL, 'rfm' => SegmentCombineMode::ALL],
        ]);

        $this->assertSame(['birthday_months' => [1, 7]], $criteria);
        $this->assertEqualsCanonicalizing([$july->id, $january->id], $service->getMemberIds($project->id, $criteria)->all());
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
    public function test_coupon_admin_create_スマレジ契約ありなら_po_s登録後に保存すること(): void
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

    public function test_coupon_admin_create_スマレジ失敗時は_d_b保存しないこと(): void
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

    public function test_保有クーポンの未使用と使用済みで会員を絞り込めること(): void
    {
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $unusedMember = Member::factory()->create(['project_id' => $project->id]);
        $usedMember = Member::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id]);

        MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $unusedMember->id,
            'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_UNUSED,
        ]);
        MemberCoupon::factory()->create([
            'project_id' => $project->id,
            'member_id' => $usedMember->id,
            'coupon_id' => $coupon->id,
            'status' => MemberCoupon::STATUS_USED,
            'used_at' => now(),
        ]);

        $segment = app(MemberSegmentService::class);
        $unusedIds = $segment->getMemberIds($project->id, [
            'held_coupon_id' => $coupon->id,
            'held_coupon_status' => 'unused',
        ]);
        $usedIds = $segment->getMemberIds($project->id, [
            'held_coupon_id' => $coupon->id,
            'held_coupon_status' => 'used',
        ]);

        $this->assertEqualsCanonicalizing([$unusedMember->id], $unusedIds->all());
        $this->assertEqualsCanonicalizing([$usedMember->id], $usedIds->all());
    }

    public function test_購入商品でアプリ注文とpos来店の会員を絞り込めること(): void
    {
        $project = $this->createProject();
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'smaregi_product_id' => 'P-100',
            'name' => '対象商品',
        ]);

        $buyerUser = User::factory()->create(['project_id' => $project->id]);
        $buyer = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $buyerUser->id,
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $buyerUser->id,
            'order_number' => 'ORD-PRODUCT-1',
            'order_type' => 'takeout',
            'total_amount' => 500,
            'status' => 'completed',
        ]);
        OrderItem::create([
            'project_id' => $project->id,
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 500,
        ]);

        $posUser = User::factory()->create(['project_id' => $project->id]);
        $posMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $posUser->id,
        ]);
        $visit = MemberVisit::create([
            'project_id' => $project->id,
            'member_id' => $posMember->id,
            'visited_at' => now(),
            'source' => MemberVisitSource::SMAREGI,
            'amount' => 1000,
            'smaregi_transaction_head_id' => 'TX-1',
        ]);
        MemberVisitItem::create([
            'project_id' => $project->id,
            'member_visit_id' => $visit->id,
            'product_id' => $product->id,
            'smaregi_product_id' => 'P-100',
            'product_name' => '対象商品',
            'quantity' => 1,
            'unit_price' => 1000,
            'amount' => 1000,
            'sort_order' => 0,
        ]);

        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
        ]);

        $ids = app(MemberSegmentService::class)->getMemberIds($project->id, [
            'product_id' => $product->id,
        ]);

        $this->assertEqualsCanonicalizing([$buyer->id, $posMember->id], $ids->all());
    }

    public function test_rfm_segments_指定セグメントの会員を絞り込めること(): void
    {
        $project = $this->createProject();
        $neverMember = Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
        ]);
        $activeUser = User::factory()->create(['project_id' => $project->id]);
        Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => $activeUser->id,
        ]);
        $order = Order::create([
            'project_id' => $project->id,
            'user_id' => $activeUser->id,
            'order_number' => 'ORD-'.uniqid(),
            'order_type' => 'take_out',
            'total_amount' => 800,
            'status' => OrderStatus::COMPLETED,
        ]);
        $order->forceFill(['created_at' => now()->subDays(3)])->save();

        $ids = app(MemberSegmentService::class)->getMemberIds($project->id, [
            'rfm_segments' => ['never'],
        ]);

        $this->assertSame([$neverMember->id], $ids->all());
    }
}
