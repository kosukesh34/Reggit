<?php

namespace Tests\Feature;

use App\Enums\LotteryRewardType;
use App\Enums\LotterySourceType;
use App\Enums\MemberCouponSource;
use App\Enums\StampCycleMode;
use App\Enums\StampRewardType;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\MemberLotteryEntitlement;
use App\Models\Project;
use App\Models\StampCard;
use App\Models\User;
use App\Services\Admin\MemberBenefitService;
use App\Services\CouponIssueService;
use App\Services\Smaregi\SmaregiInboundAwardApplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class StampMilestoneRewardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_スタンプ到達でクーポンとガチャが付与されること(): void
    {
        [$member, $card, $coupon] = $this->createRewardFixtures();

        resolve(MemberBenefitService::class)->addStampsToMember($member, (int) $card->id, 10);

        $this->assertDatabaseHas('member_coupons', [
            'project_id' => $member->project_id,
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
        ]);
        $this->assertDatabaseHas('member_lottery_entitlements', [
            'project_id' => $member->project_id,
            'member_id' => $member->id,
            'reward_type' => LotteryRewardType::GACHA,
            'source_type' => LotterySourceType::STAMP,
            'source_id' => $card->id.':'.$member->id.':0:10',
            'is_done' => false,
        ]);
        $this->assertSame(1, MemberCoupon::query()->where('member_id', $member->id)->count());
    }

    public function test_同じマイルストーンではクーポンとガチャを再付与しないこと(): void
    {
        [$member, $card] = $this->createRewardFixtures();
        $service = resolve(MemberBenefitService::class);
        $service->addStampsToMember($member, (int) $card->id, 10);
        $service->adjustStampsToMember($member, (int) $card->id, -5, '減算');
        $service->addStampsToMember($member, (int) $card->id, 5);

        $this->assertSame(1, MemberCoupon::query()->where('member_id', $member->id)->count());
        $this->assertSame(1, MemberLotteryEntitlement::query()->where('member_id', $member->id)->count());
    }

    public function test_減算では特典を付与しないこと(): void
    {
        [$member, $card] = $this->createRewardFixtures();
        resolve(MemberBenefitService::class)->addStampsToMember($member, (int) $card->id, 10);
        MemberCoupon::query()->delete();
        MemberLotteryEntitlement::query()->delete();

        resolve(MemberBenefitService::class)->adjustStampsToMember($member, (int) $card->id, -3, '減算');

        $this->assertSame(0, MemberCoupon::query()->count());
        $this->assertSame(0, MemberLotteryEntitlement::query()->count());
    }

    public function test_未対応の特典タイプではクーポンもガチャも付与しないこと(): void
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $card = StampCard::factory()->create([
            'project_id' => $project->id,
            'required_stamps' => 10,
            'is_active' => true,
            'reward_milestones' => [
                [
                    'stamp_count' => 5,
                    'reward_type' => 'text',
                    'description' => 'ドリンク1杯無料',
                ],
            ],
        ]);

        resolve(MemberBenefitService::class)->addStampsToMember($member, (int) $card->id, 5);

        $this->assertSame(0, MemberCoupon::query()->count());
        $this->assertSame(0, MemberLotteryEntitlement::query()->count());
    }

    public function test_未使用の達成クーポンは取引キャンセルで取り消され再付与できること(): void
    {
        [$member, $card, $coupon] = $this->createRewardFixtures(StampCycleMode::REPEAT, 10);
        $service = resolve(MemberBenefitService::class);
        $service->addStampsToMember($member, (int) $card->id, 9);
        $service->addStampsToMember(
            $member,
            (int) $card->id,
            5,
            'スマレジ取引によるスタンプ付与 #head-1',
            null,
            MemberCouponSource::SMAREGI_TRANSACTION,
            'head-1',
        );

        $this->assertDatabaseHas('member_coupons', [
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'issue_source_type' => MemberCouponSource::SMAREGI_TRANSACTION,
            'issue_source_id' => 'head-1',
            'status' => MemberCoupon::STATUS_UNUSED,
        ]);

        resolve(CouponIssueService::class)->revokeUnusedForSource(
            $member,
            MemberCouponSource::SMAREGI_TRANSACTION,
            'head-1',
        );
        $service->adjustStampsToMember($member, (int) $card->id, -5, 'スマレジ取引キャンセルによるスタンプ取消 #head-1');

        $this->assertSame(0, MemberCoupon::query()->where('member_id', $member->id)->count());

        $service->addStampsToMember(
            $member,
            (int) $card->id,
            5,
            'スマレジ取引によるスタンプ付与 #head-2',
            null,
            MemberCouponSource::SMAREGI_TRANSACTION,
            'head-2',
        );

        $this->assertSame(1, MemberCoupon::query()->where('member_id', $member->id)->count());
        $this->assertDatabaseHas('member_coupons', [
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'issue_source_id' => 'head-2',
            'status' => MemberCoupon::STATUS_UNUSED,
        ]);
    }

    public function test_使用済みの達成クーポンは取引キャンセルで残ること(): void
    {
        [$member, $card, $coupon] = $this->createRewardFixtures(StampCycleMode::REPEAT, 10);
        $service = resolve(MemberBenefitService::class);
        $service->addStampsToMember($member, (int) $card->id, 9);
        $service->addStampsToMember(
            $member,
            (int) $card->id,
            5,
            'スマレジ取引によるスタンプ付与 #head-used',
            null,
            MemberCouponSource::SMAREGI_TRANSACTION,
            'head-used',
        );
        $memberCoupon = MemberCoupon::query()->where('member_id', $member->id)->first();
        $this->assertNotNull($memberCoupon);
        $memberCoupon->status = MemberCoupon::STATUS_USED;
        $memberCoupon->used_at = now();
        $memberCoupon->save();

        resolve(CouponIssueService::class)->revokeUnusedForSource(
            $member,
            MemberCouponSource::SMAREGI_TRANSACTION,
            'head-used',
        );

        $this->assertSame(1, MemberCoupon::query()->where('member_id', $member->id)->count());
        $this->assertDatabaseHas('member_coupons', [
            'id' => $memberCoupon->id,
            'status' => MemberCoupon::STATUS_USED,
            'coupon_id' => $coupon->id,
        ]);
    }

    public function test_他取引の未使用クーポンは取り消さないこと(): void
    {
        [$member, $card] = $this->createRewardFixtures(StampCycleMode::REPEAT, 10);
        resolve(MemberBenefitService::class)->addStampsToMember(
            $member,
            (int) $card->id,
            10,
            'スマレジ取引によるスタンプ付与 #keep',
            null,
            MemberCouponSource::SMAREGI_TRANSACTION,
            'keep',
        );

        resolve(CouponIssueService::class)->revokeUnusedForSource(
            $member,
            MemberCouponSource::SMAREGI_TRANSACTION,
            'other',
        );

        $this->assertSame(1, MemberCoupon::query()->where('member_id', $member->id)->count());
    }

    public function test_スマレジ取引キャンセルで未使用達成クーポンを取り消すこと(): void
    {
        [$member, $card, $coupon] = $this->createRewardFixtures(StampCycleMode::REPEAT, 10);
        ShopSettingStore::setValue('stamp_earn_per_amount', 100, 'integer', (int) $member->project_id);
        resolve(MemberBenefitService::class)->addStampsToMember($member, (int) $card->id, 9);

        $applier = resolve(SmaregiInboundAwardApplier::class);
        $applier->awardForPurchase($member, 500, '39', true);
        $this->assertDatabaseHas('member_coupons', [
            'member_id' => $member->id,
            'coupon_id' => $coupon->id,
            'issue_source_type' => MemberCouponSource::SMAREGI_TRANSACTION,
            'issue_source_id' => '39',
            'status' => MemberCoupon::STATUS_UNUSED,
        ]);

        $applier->reverseForCancel($member, '39');

        $this->assertSame(0, MemberCoupon::query()->where('member_id', $member->id)->count());
    }

    public function test_繰り返すカードは周ごとにクーポンを配布すること(): void
    {
        [$member, $card, $coupon] = $this->createRewardFixtures(StampCycleMode::REPEAT, 10);
        $service = resolve(MemberBenefitService::class);
        $service->addStampsToMember(
            $member,
            (int) $card->id,
            10,
            'スマレジ取引によるスタンプ付与 #lap-1',
            null,
            MemberCouponSource::SMAREGI_TRANSACTION,
            'lap-1',
        );
        $service->addStampsToMember(
            $member,
            (int) $card->id,
            10,
            'スマレジ取引によるスタンプ付与 #lap-2',
            null,
            MemberCouponSource::SMAREGI_TRANSACTION,
            'lap-2',
        );

        $issued = MemberCoupon::query()->where('member_id', $member->id)->orderBy('id')->get();
        $this->assertSame(2, $issued->count());
        $this->assertSame('lap-1', $issued[0]->issue_source_id);
        $this->assertSame('lap-2', $issued[1]->issue_source_id);
        $this->assertSame($coupon->id, $issued[0]->coupon_id);
        $this->assertNotSame($issued[0]->issue_grant_key, $issued[1]->issue_grant_key);
    }

    public function test_複数取引のうちキャンセルした取引の未使用クーポンだけ取り消すこと(): void
    {
        [$member, $card] = $this->createRewardFixtures(StampCycleMode::REPEAT, 10);
        $service = resolve(MemberBenefitService::class);
        $service->addStampsToMember(
            $member,
            (int) $card->id,
            10,
            'スマレジ取引によるスタンプ付与 #a',
            null,
            MemberCouponSource::SMAREGI_TRANSACTION,
            'a',
        );
        $service->addStampsToMember(
            $member,
            (int) $card->id,
            10,
            'スマレジ取引によるスタンプ付与 #b',
            null,
            MemberCouponSource::SMAREGI_TRANSACTION,
            'b',
        );
        $this->assertSame(2, MemberCoupon::query()->where('member_id', $member->id)->count());

        resolve(CouponIssueService::class)->revokeUnusedForSource(
            $member,
            MemberCouponSource::SMAREGI_TRANSACTION,
            'a',
        );

        $this->assertSame(1, MemberCoupon::query()->where('member_id', $member->id)->count());
        $this->assertDatabaseHas('member_coupons', [
            'member_id' => $member->id,
            'issue_source_id' => 'b',
            'status' => MemberCoupon::STATUS_UNUSED,
        ]);
    }

    public function test_1取引で複数周した場合は周の数だけ配布しキャンセルで未使用をまとめて取り消すこと(): void
    {
        [$member, $card] = $this->createRewardFixtures(StampCycleMode::REPEAT, 10);
        resolve(MemberBenefitService::class)->addStampsToMember(
            $member,
            (int) $card->id,
            20,
            'スマレジ取引によるスタンプ付与 #wrap',
            null,
            MemberCouponSource::SMAREGI_TRANSACTION,
            'wrap',
        );
        $this->assertSame(2, MemberCoupon::query()->where('member_id', $member->id)->count());
        $this->assertSame(2, MemberCoupon::query()
            ->where('member_id', $member->id)
            ->where('issue_source_id', 'wrap')
            ->count());

        resolve(CouponIssueService::class)->revokeUnusedForSource(
            $member,
            MemberCouponSource::SMAREGI_TRANSACTION,
            'wrap',
        );

        $this->assertSame(0, MemberCoupon::query()->where('member_id', $member->id)->count());
    }

    public function test_同じ取引のキャンセルを繰り返しても未使用クーポン取消は冪等であること(): void
    {
        [$member, $card, $coupon] = $this->createRewardFixtures(StampCycleMode::REPEAT, 10);
        ShopSettingStore::setValue('stamp_earn_per_amount', 100, 'integer', (int) $member->project_id);
        resolve(MemberBenefitService::class)->addStampsToMember($member, (int) $card->id, 9);

        $applier = resolve(SmaregiInboundAwardApplier::class);
        $applier->awardForPurchase($member, 500, '39', true);
        $this->assertSame(1, MemberCoupon::query()->where('coupon_id', $coupon->id)->count());
        $applier->reverseForCancel($member, '39');
        $stampsAfterFirst = $member->fresh()->getTotalStamps();
        $applier->reverseForCancel($member, '39');

        $this->assertSame(0, MemberCoupon::query()->where('member_id', $member->id)->count());
        $this->assertSame($stampsAfterFirst, $member->fresh()->getTotalStamps());
    }

    /**
     * @return array{0: Member, 1: StampCard, 2: Coupon}
     */
    private function createRewardFixtures(string $cycleMode = StampCycleMode::ONCE, int $couponStampCount = 5): array
    {
        $project = Project::factory()->create();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'name' => 'デザート割引',
            'is_active' => true,
        ]);
        $card = StampCard::factory()->create([
            'project_id' => $project->id,
            'required_stamps' => 10,
            'is_active' => true,
            'cycle_mode' => $cycleMode,
            'reward_milestones' => [
                [
                    'stamp_count' => $couponStampCount,
                    'reward_type' => StampRewardType::COUPON,
                    'coupon_id' => $coupon->id,
                    'description' => 'デザート割引',
                ],
                [
                    'stamp_count' => 10,
                    'reward_type' => StampRewardType::GACHA,
                    'description' => StampRewardType::GACHA_DESCRIPTION,
                ],
            ],
        ]);

        return [$member, $card, $coupon];
    }
}
