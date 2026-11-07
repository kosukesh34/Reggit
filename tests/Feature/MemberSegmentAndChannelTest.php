<?php

namespace Tests\Feature;

use App\Enums\MemberVisitSource;
use App\Enums\OrderStatus;
use App\Enums\SegmentCombineMode;
use App\Infrastructure\Persistence\Eloquent\Coupon\EloquentCouponDeliveryTargetRepository;
use App\Models\AcquisitionSource;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\LineFollowEvent;
use App\Models\Member;
use App\Models\MemberAudienceGroup;
use App\Models\MemberCoupon;
use App\Models\MemberLotteryEntitlement;
use App\Models\MemberRankHistory;
use App\Models\MemberSubscription;
use App\Models\MemberTicket;
use App\Models\MemberVisit;
use App\Models\MemberVisitItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PointTransaction;
use App\Models\Product;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Models\Reservation;
use App\Models\Store;
use App\Models\Survey;
use App\Models\SurveyResponse;
use App\Models\User;
use App\Models\UserDevice;
use App\Models\WalletPassRegistration;
use App\Services\Admin\CouponAdminService;
use App\Services\Admin\MemberSegmentCriteriaDescriber;
use App\Services\Admin\MemberSegmentService;
use App\Services\Member\MemberPointLedger;
use App\Services\Smaregi\SmaregiCouponSyncService;
use App\Support\MemberChannel;
use Carbon\Carbon;
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

    public function test_保有クーポンの期限切れと未配布で会員を絞り込めること(): void
    {
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $validMember = Member::factory()->create(['project_id' => $project->id]);
        $expiredMember = Member::factory()->create(['project_id' => $project->id]);
        $usedMember = Member::factory()->create(['project_id' => $project->id]);
        $notIssuedMember = Member::factory()->create(['project_id' => $project->id]);
        foreach ([
            [$validMember, MemberCoupon::STATUS_UNUSED, now()->addDay()],
            [$expiredMember, MemberCoupon::STATUS_UNUSED, now()->subDay()],
            [$usedMember, MemberCoupon::STATUS_USED, now()->subDay()],
        ] as [$member, $status, $validUntil]) {
            MemberCoupon::factory()->create([
                'project_id' => $project->id,
                'member_id' => $member->id,
                'coupon_id' => $coupon->id,
                'status' => $status,
                'valid_until' => $validUntil,
            ]);
        }

        $segment = app(MemberSegmentService::class);
        $ids = fn (string $status): array => $segment->getMemberIds($project->id, $segment->buildCriteriaFromInput([
            'segment_coupon_id' => (string) $coupon->id,
            'segment_coupon_status' => $status,
        ]) ?? [])->all();

        $this->assertEqualsCanonicalizing([$validMember->id], $ids('unused'));
        $this->assertEqualsCanonicalizing([$expiredMember->id], $ids('expired'));
        $this->assertEqualsCanonicalizing([$usedMember->id], $ids('used'));
        $this->assertEqualsCanonicalizing([$notIssuedMember->id], $ids('not_issued'));
    }

    public function test_同じ種類の条件を複数追加して絞り込めること(): void
    {
        $project = $this->createProject();
        $couponA = Coupon::factory()->create(['project_id' => $project->id, 'name' => 'クーポンA']);
        $couponB = Coupon::factory()->create(['project_id' => $project->id, 'name' => 'クーポンB']);
        $target = Member::factory()->create(['project_id' => $project->id]);
        $hasBoth = Member::factory()->create(['project_id' => $project->id]);
        $hasNeither = Member::factory()->create(['project_id' => $project->id]);
        foreach ([[$target, $couponA], [$hasBoth, $couponA], [$hasBoth, $couponB]] as [$member, $coupon]) {
            MemberCoupon::factory()->create([
                'project_id' => $project->id,
                'member_id' => $member->id,
                'coupon_id' => $coupon->id,
                'status' => MemberCoupon::STATUS_UNUSED,
                'valid_until' => now()->addDay(),
            ]);
        }

        $segment = app(MemberSegmentService::class);
        $input = [
            'segment_coupon_id' => (string) $couponA->id,
            'segment_coupon_status' => 'unused',
            'segment_more' => [
                ['segment_coupon_id' => (string) $couponB->id, 'segment_coupon_status' => 'not_issued'],
                ['segment_coupon_id' => '', 'segment_coupon_status' => ''],
            ],
        ];
        $criteria = $segment->buildCriteriaFromInput($input);

        $this->assertCount(1, $criteria['more']);
        $this->assertSame([$target->id], $segment->getMemberIds($project->id, $criteria)->all());
        $this->assertEqualsCanonicalizing(
            [$target->id, $hasBoth->id, $hasNeither->id],
            $segment->getMemberIds($project->id, $segment->buildCriteriaFromInput($input + ['segment_match_mode' => 'any']))->all(),
        );
        $this->assertSame(
            ['保有クーポン：クーポンA（未使用）', 'かつ 保有クーポン：クーポンB（未配布）'],
            app(MemberSegmentCriteriaDescriber::class)->describe($project->id, $criteria),
        );
        $this->assertNotNull($segment->buildCriteriaFromInput(['segment_more' => [['segment_sex' => '2']]]));
    }

    public function test_条件のまとまりごとに組み合わせ方を選べること(): void
    {
        $project = $this->createProject();
        $couponA = Coupon::factory()->create(['project_id' => $project->id, 'name' => 'クーポンA']);
        $couponB = Coupon::factory()->create(['project_id' => $project->id, 'name' => 'クーポンB']);
        $womanWithA = Member::factory()->create(['project_id' => $project->id, 'sex' => 2]);
        $womanWithB = Member::factory()->create(['project_id' => $project->id, 'sex' => 2]);
        $manWithA = Member::factory()->create(['project_id' => $project->id, 'sex' => 1]);
        Member::factory()->create(['project_id' => $project->id, 'sex' => 2]);
        foreach ([[$womanWithA, $couponA], [$womanWithB, $couponB], [$manWithA, $couponA]] as [$member, $coupon]) {
            MemberCoupon::factory()->create([
                'project_id' => $project->id,
                'member_id' => $member->id,
                'coupon_id' => $coupon->id,
                'status' => MemberCoupon::STATUS_UNUSED,
                'valid_until' => now()->addDay(),
            ]);
        }

        $segment = app(MemberSegmentService::class);
        // 女性 かつ（クーポンA未使用 または クーポンB未使用）
        $criteria = $segment->buildCriteriaFromInput([
            'segment_sex' => '2',
            'segment_blocks' => [
                [
                    'segment_match_mode' => 'any',
                    'segment_more' => [
                        ['segment_coupon_id' => (string) $couponA->id, 'segment_coupon_status' => 'unused'],
                        ['segment_coupon_id' => (string) $couponB->id, 'segment_coupon_status' => 'unused'],
                    ],
                ],
                ['segment_match_mode' => 'all', 'segment_audience_group_ids' => ['1']],
            ],
        ]);

        $this->assertCount(1, $criteria['blocks']);
        $this->assertEqualsCanonicalizing([$womanWithA->id, $womanWithB->id], $segment->getMemberIds($project->id, $criteria)->all());
        $this->assertSame(
            ['性別：女性', 'かつ （保有クーポン：クーポンA（未使用） または 保有クーポン：クーポンB（未使用））'],
            app(MemberSegmentCriteriaDescriber::class)->describe($project->id, $criteria),
        );
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

    public function test_お知らせの既読と未読で会員を絞り込めること(): void
    {
        $project = $this->createProject();
        $reader = Member::factory()->create(['project_id' => $project->id]);
        $unreadRecipient = Member::factory()->create(['project_id' => $project->id]);
        $notRecipient = Member::factory()->create(['project_id' => $project->id]);
        $targeted = Announcement::factory()->create([
            'project_id' => $project->id,
            'title' => '秋の新作',
            'delivery_type' => Announcement::DELIVERY_TYPE_SEGMENT,
            'target_member_ids' => [$reader->id, $unreadRecipient->id],
        ]);
        $broadcast = Announcement::factory()->create(['project_id' => $project->id, 'target_member_ids' => null]);
        foreach ([$targeted, $broadcast] as $announcement) {
            AnnouncementRead::create([
                'project_id' => $project->id,
                'announcement_id' => $announcement->id,
                'user_id' => $reader->user_id,
                'read_at' => now(),
            ]);
        }

        $segment = app(MemberSegmentService::class);
        $ids = fn (int $announcementId, string $status): array => $segment->getMemberIds($project->id, $segment->buildCriteriaFromInput([
            'segment_announcement_id' => (string) $announcementId,
            'segment_announcement_status' => $status,
        ]) ?? [])->all();

        $this->assertEqualsCanonicalizing([$reader->id], $ids($targeted->id, 'read'));
        // 宛先に入っていない会員は「未読」に含めない
        $this->assertEqualsCanonicalizing([$unreadRecipient->id], $ids($targeted->id, 'unread'));
        $this->assertEqualsCanonicalizing([$unreadRecipient->id, $notRecipient->id], $ids($broadcast->id, 'unread'));
        $this->assertNull($segment->buildCriteriaFromInput(['segment_announcement_id' => (string) $targeted->id, 'segment_announcement_status' => 'other']));
        $this->assertSame(
            ['お知らせ：秋の新作（未読）'],
            app(MemberSegmentCriteriaDescriber::class)->describe($project->id, ['read_announcement_id' => $targeted->id, 'read_announcement_status' => 'unread']),
        );
    }

    public function test_登録日と年齢とポイント残高で会員を絞り込めること(): void
    {
        $this->travelTo(Carbon::parse('2026-06-15 12:00:00'));
        $project = $this->createProject();
        $old = Member::factory()->create(['project_id' => $project->id, 'created_at' => '2025-12-31 23:59:59', 'birthday' => '2006-06-15', 'points' => 100]);
        $recent = Member::factory()->create(['project_id' => $project->id, 'created_at' => '2026-01-01 00:00:00', 'birthday' => '2006-06-16', 'points' => 500]);
        $noBirthday = Member::factory()->create(['project_id' => $project->id, 'created_at' => '2026-03-31 23:59:59', 'birthday' => null, 'points' => 3000]);

        $segment = app(MemberSegmentService::class);
        $ids = fn (array $input): array => $segment->getMemberIds($project->id, $segment->buildCriteriaFromInput($input) ?? [])->all();

        $this->assertEqualsCanonicalizing([$recent->id, $noBirthday->id], $ids(['segment_registered_from' => '2026-01-01']));
        $this->assertEqualsCanonicalizing([$old->id, $recent->id], $ids(['segment_registered_to' => '2026-01-01']));
        $this->assertEqualsCanonicalizing([$noBirthday->id], $ids(['segment_registered_from' => '2026-03-31', 'segment_registered_to' => '2026-03-31']));
        // 2006-06-15 生まれは今日で20歳、2006-06-16 生まれはまだ19歳
        $this->assertEqualsCanonicalizing([$old->id], $ids(['segment_age_min' => '20']));
        $this->assertEqualsCanonicalizing([$recent->id], $ids(['segment_age_max' => '19']));
        $this->assertEqualsCanonicalizing([$recent->id, $noBirthday->id], $ids(['segment_points_min' => '500']));
        $this->assertEqualsCanonicalizing([$old->id, $recent->id], $ids(['segment_points_min' => '100', 'segment_points_max' => '500']));
        $this->assertNull($segment->buildCriteriaFromInput(['segment_registered_from' => '2026-02-30']));

        $this->assertSame(
            ['登録日：2026/01/01以降', 'かつ 年齢：20〜29歳', 'かつ ポイント残高：500pt以上'],
            app(MemberSegmentCriteriaDescriber::class)->describe($project->id, $segment->buildCriteriaFromInput([
                'segment_points_min' => '500',
                'segment_age_min' => '20',
                'segment_age_max' => '29',
                'segment_registered_from' => '2026-01-01',
            ])),
        );
    }

    public function test_店舗_登録経路_アプリ利用_アンケート_予約_サブスク_回数券_クーポン利用_ランク変更_walletで絞り込めること(): void
    {
        $project = $this->createProject();
        $member = fn (): Member => Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id])->id,
        ]);
        $a = $member();
        $b = $member();
        $c = $member();
        $segment = app(MemberSegmentService::class);
        $ids = fn (array $input): array => $segment->getMemberIds($project->id, $segment->buildCriteriaFromInput($input) ?? [])->all();

        $storeA = Store::create(['project_id' => $project->id, 'store_key' => 'seg-a', 'name' => '渋谷店']);
        $a->stores()->attach($storeA->id, ['project_id' => $project->id]);
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_store_ids' => [(string) $storeA->id]]));

        $flyer = AcquisitionSource::create(['project_id' => $project->id, 'name' => 'チラシ', 'code' => 'flyer']);
        $b->forceFill(['acquisition_source_id' => $flyer->id])->save();
        $this->assertEqualsCanonicalizing([$b->id], $ids(['segment_acquisition_source_ids' => [(string) $flyer->id]]));

        foreach ([[$a, now()->subDays(3)], [$b, now()->subDays(60)]] as [$owner, $usedAt]) {
            UserDevice::create(['project_id' => $project->id, 'user_id' => $owner->user_id, 'platform' => 'ios', 'device_identifier' => 'dev-'.$owner->id, 'last_used_at' => $usedAt]);
        }
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_app_last_used_mode' => 'within_days', 'segment_app_last_used_days' => '30']));
        // アプリを入れていない $c は「利用なし」に含めない
        $this->assertEqualsCanonicalizing([$b->id], $ids(['segment_app_last_used_mode' => 'inactive_days', 'segment_app_last_used_days' => '30']));
        $this->assertNull($segment->buildCriteriaFromInput(['segment_app_last_used_mode' => 'within_days', 'segment_app_last_used_days' => '45']));

        $survey = Survey::create(['project_id' => $project->id, 'title' => '満足度調査', 'questions' => []]);
        SurveyResponse::create(['project_id' => $project->id, 'survey_id' => $survey->id, 'member_id' => $a->id, 'answered_at' => now()]);
        SurveyResponse::create(['project_id' => $project->id, 'survey_id' => $survey->id, 'member_id' => $b->id, 'invited_at' => now()]);
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_survey_id' => (string) $survey->id, 'segment_survey_status' => 'answered']));
        $this->assertEqualsCanonicalizing([$b->id, $c->id], $ids(['segment_survey_id' => (string) $survey->id, 'segment_survey_status' => 'unanswered']));

        foreach ([[$a, 'confirmed', now()->addDay()], [$b, 'cancelled', now()->subDay()]] as $index => [$owner, $status, $reservedAt]) {
            Reservation::create(['project_id' => $project->id, 'user_id' => $owner->user_id, 'store_id' => $storeA->id, 'reservation_number' => 'SEG-'.$index, 'reserved_at' => $reservedAt, 'status' => $status]);
        }
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_reservation_mode' => 'has', 'segment_reservation_days' => '30']));
        $this->assertEqualsCanonicalizing([$b->id, $c->id], $ids(['segment_reservation_mode' => 'none', 'segment_reservation_days' => '30']));

        foreach ([[$a, 'active'], [$b, 'canceled']] as [$owner, $status]) {
            MemberSubscription::create(['project_id' => $project->id, 'member_id' => $owner->id, 'payment_setting_id' => 1, 'stripe_subscription_id' => 'sub_'.$owner->id, 'name' => '月額', 'status' => $status]);
        }
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_subscription_status' => 'active']));
        $this->assertEqualsCanonicalizing([$b->id], $ids(['segment_subscription_status' => 'ended']));
        $this->assertEqualsCanonicalizing([$c->id], $ids(['segment_subscription_status' => 'none']));

        foreach ([[$a, 5, 2, null], [$b, 2, 2, null], [$c, 5, 0, now()->subDay()]] as [$owner, $total, $used, $expiresAt]) {
            $order = Order::create(['project_id' => $project->id, 'user_id' => $owner->user_id, 'store_id' => $storeA->id, 'order_number' => 'TICKET-'.$owner->id, 'order_type' => 'takeout', 'total_amount' => 1000, 'status' => 'completed']);
            $item = OrderItem::create(['project_id' => $project->id, 'order_id' => $order->id, 'product_id' => Product::factory()->create(['project_id' => $project->id])->id, 'quantity' => 1, 'price' => 1000]);
            MemberTicket::create(['project_id' => $project->id, 'member_id' => $owner->id, 'order_id' => $order->id, 'order_item_id' => $item->id, 'name' => '回数券', 'total_uses' => $total, 'used_count' => $used, 'expires_at' => $expiresAt]);
        }
        // $b は使い切り、$c は期限切れ
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_ticket_status' => 'active']));
        $this->assertEqualsCanonicalizing([$b->id, $c->id], $ids(['segment_ticket_status' => 'none']));

        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        foreach ([$a, $a, $b] as $owner) {
            CouponRedemption::create(['project_id' => $project->id, 'store_id' => $storeA->id, 'coupon_id' => $coupon->id, 'member_id' => $owner->id, 'used_at' => now()]);
        }
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_coupon_uses_min' => '2']));
        $this->assertEqualsCanonicalizing([$c->id], $ids(['segment_coupon_uses_max' => '0']));

        MemberRankHistory::create(['project_id' => $project->id, 'member_id' => $a->id, 'to_rank' => 'gold', 'to_rank_label' => 'ゴールド', 'achieved_at' => now()->subDays(5)]);
        MemberRankHistory::create(['project_id' => $project->id, 'member_id' => $b->id, 'to_rank' => 'gold', 'to_rank_label' => 'ゴールド', 'achieved_at' => now()->subDays(40)]);
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_rank_changed_days' => '30']));

        WalletPassRegistration::create(['project_id' => $project->id, 'member_id' => $c->id, 'pass_type_identifier' => 'pass.test', 'serial_number' => 'S1', 'device_library_identifier' => 'D1', 'push_token' => 'T1']);
        $this->assertEqualsCanonicalizing([$c->id], $ids(['segment_wallet_status' => 'registered']));
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $ids(['segment_wallet_status' => 'not_registered']));

        $this->assertSame(
            ['登録経路：チラシ', 'かつ 利用店舗：渋谷店', 'かつ 予約（直近30日と今後）：あり', 'かつ サブスク：継続中', 'かつ アンケート：満足度調査（未回答）', 'かつ Apple Wallet：会員証を未追加'],
            app(MemberSegmentCriteriaDescriber::class)->describe($project->id, $segment->buildCriteriaFromInput([
                'segment_wallet_status' => 'not_registered',
                'segment_survey_id' => (string) $survey->id,
                'segment_survey_status' => 'unanswered',
                'segment_subscription_status' => 'active',
                'segment_reservation_mode' => 'has',
                'segment_reservation_days' => '30',
                'segment_store_ids' => [(string) $storeA->id],
                'segment_acquisition_source_ids' => [(string) $flyer->id],
            ])),
        );
    }

    public function test_誕生日_購入_順番待ち_失効_くじ_評価_line_通知の条件で絞り込めること(): void
    {
        $this->travelTo(Carbon::parse('2026-02-25 10:00:00'));
        $project = $this->createProject();
        $member = fn (array $user = [], array $attributes = []): Member => Member::factory()->create([
            'project_id' => $project->id,
            'user_id' => User::factory()->create(['project_id' => $project->id, ...$user])->id,
            'birthday' => null,
            ...$attributes,
        ]);
        $a = $member(['line_user_id' => $this->validLineUserId('a')], ['birthday' => '1990-02-28', 'notification_preference' => 'all']);
        $b = $member(['line_user_id' => $this->validLineUserId('b'), 'line_blocked_at' => now()], ['birthday' => '2000-02-29', 'notification_preference' => 'none']);
        $c = $member([], ['birthday' => '1995-03-05', 'notification_preference' => 'coupon_only']);
        $segment = app(MemberSegmentService::class);
        $ids = fn (array $input): array => $segment->getMemberIds($project->id, $segment->buildCriteriaFromInput($input) ?? [])->all();

        // 2026 年はうるう年でないため、2/29 生まれは 2/28 に含める
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $ids(['segment_birthday_soon_mode' => 'exact', 'segment_birthday_soon_days' => '3']));
        $this->assertEqualsCanonicalizing([$a->id, $b->id, $c->id], $ids(['segment_birthday_soon_mode' => 'within', 'segment_birthday_soon_days' => '14']));
        $this->assertSame([], $ids(['segment_birthday_soon_mode' => 'within', 'segment_birthday_soon_days' => '0']));

        foreach ([[$a, 'pos', 20000, 'completed'], [$a, 'mobile', 15000, 'completed'], [$b, 'mobile', 50000, 'cancelled']] as $index => [$owner, $type, $amount, $status]) {
            Order::create(['project_id' => $project->id, 'user_id' => $owner->user_id, 'order_number' => 'LTV-'.$index, 'order_type' => $type, 'total_amount' => $amount, 'status' => $status]);
        }
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_lifetime_sales_min' => '30000']));
        $this->assertEqualsCanonicalizing([$b->id, $c->id], $ids(['segment_lifetime_sales_max' => '0']));
        // キャンセルした注文は数えない
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_order_types' => ['mobile']]));
        $this->assertNull($segment->buildCriteriaFromInput(['segment_order_types' => ['unknown']]));

        QueueEntry::create(['project_id' => $project->id, 'member_id' => $c->id, 'status' => 'entered', 'queue_number' => 1]);
        QueueEntry::create(['project_id' => $project->id, 'user_id' => $b->user_id, 'status' => 'cancelled', 'queue_number' => 2]);
        $this->assertEqualsCanonicalizing([$c->id], $ids(['segment_queue_days' => '7']));

        $a->forceFill(['points' => 100])->save();
        $b->forceFill(['points' => 50])->save();
        PointTransaction::create(['project_id' => $project->id, 'member_id' => $a->id, 'type' => 'earned', 'points' => 100, 'expires_at' => now()->addDays(2)]);
        // $b は期限の近い 100pt を先に使っているので、残りの 50pt（期限なし）は失効しない
        PointTransaction::create(['project_id' => $project->id, 'member_id' => $b->id, 'type' => 'earned', 'points' => 100, 'expires_at' => now()->addDays(2)]);
        PointTransaction::create(['project_id' => $project->id, 'member_id' => $b->id, 'type' => 'earned', 'points' => 50]);
        PointTransaction::create(['project_id' => $project->id, 'member_id' => $b->id, 'type' => 'used', 'points' => -100]);
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_points_expiring_days' => '3']));
        $this->assertSame([], $ids(['segment_points_expiring_days' => '0']));
        // 今日が期限のポイントは今日の終わりまで使えるので「今日中に失効」に入る
        $c->forceFill(['points' => 10])->save();
        PointTransaction::create(['project_id' => $project->id, 'member_id' => $c->id, 'type' => 'earned', 'points' => 10, 'expires_at' => now()->toDateString()]);
        $this->assertSame([$c->id], $ids(['segment_points_expiring_days' => '0']));

        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        foreach ([[$a, 'unused', now()->addDays(2)], [$b, 'used', now()->addDays(2)], [$c, 'unused', now()->addDays(20)]] as [$owner, $status, $validUntil]) {
            MemberCoupon::factory()->create(['project_id' => $project->id, 'member_id' => $owner->id, 'coupon_id' => $coupon->id, 'status' => $status, 'valid_until' => $validUntil]);
        }
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_coupon_expiring_days' => '3']));
        $this->assertEqualsCanonicalizing([$a->id, $c->id], $ids(['segment_coupon_expiring_days' => '30']));

        foreach ([[$a, false, null], [$b, true, null], [$c, false, now()->subDay()]] as $index => [$owner, $done, $expiresAt]) {
            MemberLotteryEntitlement::create(['project_id' => $project->id, 'member_id' => $owner->id, 'reward_type' => 'coupon', 'source_type' => 'test', 'source_id' => $index, 'is_done' => $done, 'expires_at' => $expiresAt]);
        }
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_lottery_status' => 'pending']));
        $this->assertEqualsCanonicalizing([$b->id, $c->id], $ids(['segment_lottery_status' => 'none']));

        $oldSurvey = Survey::create(['project_id' => $project->id, 'title' => '前回のNPS', 'questions' => []]);
        $survey = Survey::create(['project_id' => $project->id, 'title' => 'NPS', 'questions' => []]);
        foreach ([[$oldSurvey, $a, 3, now()->subDays(10)], [$survey, $a, 10, now()->subDay()], [$survey, $b, 5, now()->subDay()]] as [$answered, $owner, $score, $answeredAt]) {
            SurveyResponse::create(['project_id' => $project->id, 'survey_id' => $answered->id, 'member_id' => $owner->id, 'nps_score' => $score, 'answered_at' => $answeredAt]);
        }
        // $a は最新の回答（10）で判定する
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_nps_category' => 'promoter']));
        $this->assertEqualsCanonicalizing([$b->id], $ids(['segment_nps_category' => 'detractor']));

        foreach ([[$a, 3], [$b, 40]] as [$owner, $daysAgo]) {
            (new LineFollowEvent)->forceFill(['project_id' => $project->id, 'user_id' => $owner->user_id, 'type' => LineFollowEvent::TYPE_FOLLOW, 'created_at' => now()->subDays($daysAgo)])->save();
        }
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_line_follow_days' => '7']));

        $this->assertEqualsCanonicalizing([$b->id], $ids(['segment_line_block_status' => 'blocked']));
        // LINE 連携のない $c はどちらにも入らない
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_line_block_status' => 'not_blocked']));
        $this->assertEqualsCanonicalizing([$a->id], $ids(['segment_notification_preference' => 'all']));
        $this->assertEqualsCanonicalizing([$b->id], $ids(['segment_notification_preference' => 'none']));

        $this->assertSame(
            ['誕生日：ちょうど3日後', 'かつ ポイント失効：今日に失効予定あり', 'かつ 注文の種類：モバイル注文', 'かつ くじ：引いていない抽選権あり', 'かつ 累計購入額：30,000円以上', 'かつ アンケートの評価：低い（0〜6）', 'かつ 通知設定：通知をすべて受け取らない'],
            app(MemberSegmentCriteriaDescriber::class)->describe($project->id, $segment->buildCriteriaFromInput([
                'segment_notification_preference' => 'none',
                'segment_nps_category' => 'detractor',
                'segment_lottery_status' => 'pending',
                'segment_points_expiring_days' => '0',
                'segment_lifetime_sales_min' => '30000',
                'segment_order_types' => ['mobile'],
                'segment_birthday_soon_mode' => 'exact',
                'segment_birthday_soon_days' => '3',
            ])),
        );
    }

    public function test_ポイント台帳のまとめ計算が会員ごとの計算と同じ結果になること(): void
    {
        $project = $this->createProject();
        $members = Member::factory()->count(3)->create(['project_id' => $project->id]);
        $rows = [
            [$members[0], 'earned', 100, now()->addDays(2)],
            [$members[0], 'earned', 30, null],
            [$members[0], 'used', -50, null],
            [$members[1], 'earned', 80, now()->subDay()],
            [$members[1], 'earned', 20, now()->addDays(10)],
        ];
        foreach ($rows as [$member, $type, $points, $expiresAt]) {
            PointTransaction::create(['project_id' => $project->id, 'member_id' => $member->id, 'type' => $type, 'points' => $points, 'expires_at' => $expiresAt]);
        }
        $ledger = app(MemberPointLedger::class);

        $bulk = $ledger->balancesFor($members->pluck('id')->all());

        foreach ($members->take(2) as $member) {
            $this->assertEquals($ledger->balance($member), $bulk[$member->id]);
        }
        // 台帳がない会員は結果に含めない（失効予定なしと同じ扱い）
        $this->assertArrayNotHasKey($members[2]->id, $bulk);
    }

    public function test_既存の条件の結合順は新しい条件を足しても変わらないこと(): void
    {
        // 条件ごとの「かつ／または」は表示順に結合するため、保存済みの条件の結果を変えないよう既存の順を保つ
        $legacy = ['groups', 'channel', 'birthday', 'sex', 'rfm', 'tags', 'visits', 'rank', 'points', 'last_visit', 'product', 'held_coupon', 'stamp', 'sales'];

        $this->assertSame($legacy, array_values(array_intersect(MemberSegmentService::DISPLAY_ORDER, $legacy)));
    }

    public function test_範囲の下限が上限より大きい条件を入力エラーにできること(): void
    {
        $this->assertSame([
            'segment_registered_to' => '登録日の終わりは始まり以降の日付にしてください',
            'segment_age_max' => '年齢の上限は下限以上にしてください',
            'segment_lifetime_sales_max' => '累計購入額の上限は下限以上にしてください',
        ], MemberSegmentService::invertedRangeErrors([
            'segment_registered_from' => '2026-05-01',
            'segment_registered_to' => '2026-04-30',
            'segment_age_min' => '40',
            'segment_age_max' => '20',
            'segment_points_min' => '100',
            'segment_points_max' => '100',
            'segment_coupon_uses_min' => '3',
            'segment_coupon_uses_max' => '',
            'segment_lifetime_sales_min' => '50000',
            'segment_lifetime_sales_max' => '10000',
        ]));
    }
}
