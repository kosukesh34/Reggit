<?php

namespace Tests\Feature;

use App\Enums\LotteryRewardType;
use App\Enums\LotterySourceType;
use App\Enums\StampCycleMode;
use App\Enums\StampRewardType;
use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\MemberLotteryEntitlement;
use App\Models\Project;
use App\Models\StampCard;
use App\Models\User;
use App\Services\Admin\MemberAdminService;
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

        resolve(MemberAdminService::class)->addStampsToMember($member, (int) $card->id, 10);

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
        $service = resolve(MemberAdminService::class);
        $service->addStampsToMember($member, (int) $card->id, 10);
        $service->adjustStampsToMember($member, (int) $card->id, -5, '減算');
        $service->addStampsToMember($member, (int) $card->id, 5);

        $this->assertSame(1, MemberCoupon::query()->where('member_id', $member->id)->count());
        $this->assertSame(1, MemberLotteryEntitlement::query()->where('member_id', $member->id)->count());
    }

    public function test_減算では特典を付与しないこと(): void
    {
        [$member, $card] = $this->createRewardFixtures();
        resolve(MemberAdminService::class)->addStampsToMember($member, (int) $card->id, 10);
        MemberCoupon::query()->delete();
        MemberLotteryEntitlement::query()->delete();

        resolve(MemberAdminService::class)->adjustStampsToMember($member, (int) $card->id, -3, '減算');

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

        resolve(MemberAdminService::class)->addStampsToMember($member, (int) $card->id, 5);

        $this->assertSame(0, MemberCoupon::query()->count());
        $this->assertSame(0, MemberLotteryEntitlement::query()->count());
    }

    /**
     * @return array{0: Member, 1: StampCard, 2: Coupon}
     */
    private function createRewardFixtures(): array
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
            'cycle_mode' => StampCycleMode::ONCE,
            'reward_milestones' => [
                [
                    'stamp_count' => 5,
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
