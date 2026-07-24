<?php

namespace Tests\Feature;

use App\Enums\LotteryGrantTrigger;
use App\Enums\LotteryRewardType;
use App\Enums\LotterySourceType;
use App\Jobs\SendCouponIssuedNotificationJob;
use App\Models\Coupon;
use App\Models\LotteryPrize;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\MemberLotteryEntitlement;
use App\Models\Project;
use App\Models\Settings\ProjectLotterySetting;
use App\Models\User;
use App\Services\Lottery\LotteryEntitlementService;
use App\Services\Lottery\LotteryPlayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class LotteryEntitlementPlayTest extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U00000000000000000000000000000099';

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function enableGacha(Project $project, Coupon $coupon): void
    {
        ProjectLotterySetting::query()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'gacha_enabled' => true,
                'scratch_enabled' => false,
                'grant_trigger' => LotteryGrantTrigger::EVERY_PURCHASE,
                'min_amount' => null,
                'entitlement_expires_hours' => null,
            ]
        );
        LotteryPrize::query()->create([
            'project_id' => $project->id,
            'reward_type' => LotteryRewardType::GACHA,
            'coupon_id' => $coupon->id,
            'probability' => 100,
            'sort_order' => 0,
            'is_active' => true,
        ]);
    }

    public function test_grant_every_purchaseで権利が作成されること(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'is_active' => true]);
        $this->enableGacha($project, $coupon);

        resolve(LotteryEntitlementService::class)->grantForPurchase(
            $member,
            1500,
            LotterySourceType::ORDER,
            '101',
        );

        $this->assertDatabaseHas('member_lottery_entitlements', [
            'member_id' => $member->id,
            'reward_type' => LotteryRewardType::GACHA,
            'source_type' => LotterySourceType::ORDER,
            'source_id' => '101',
            'is_done' => false,
        ]);
    }

    public function test_grant_once_per_dayは同日2回目を付与しないこと(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'is_active' => true]);
        $this->enableGacha($project, $coupon);
        ProjectLotterySetting::query()->where('project_id', $project->id)->update([
            'grant_trigger' => LotteryGrantTrigger::ONCE_PER_DAY,
        ]);

        $service = resolve(LotteryEntitlementService::class);
        $service->grantForPurchase($member, 1000, LotterySourceType::ORDER, '1');
        $service->grantForPurchase($member, 1000, LotterySourceType::ORDER, '2');

        $this->assertSame(
            1,
            MemberLotteryEntitlement::query()
                ->where('member_id', $member->id)
                ->where('reward_type', LotteryRewardType::GACHA)
                ->count()
        );
    }

    public function test_grant_min_amount未満は付与しないこと(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'is_active' => true]);
        $this->enableGacha($project, $coupon);
        ProjectLotterySetting::query()->where('project_id', $project->id)->update([
            'grant_trigger' => LotteryGrantTrigger::MIN_AMOUNT,
            'min_amount' => 2000,
        ]);

        resolve(LotteryEntitlementService::class)->grantForPurchase(
            $member,
            1999,
            LotterySourceType::ORDER,
            '9',
        );

        $this->assertSame(0, MemberLotteryEntitlement::query()->where('member_id', $member->id)->count());
    }

    public function test_play_でクーポン発行とis_doneになること(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create([
            'line_user_id' => self::LINE_USER_ID,
            'project_id' => $project->id,
        ]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'is_active' => true,
            'name' => 'ガチャ当選クーポン',
        ]);
        $this->enableGacha($project, $coupon);
        $entitlement = MemberLotteryEntitlement::query()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'reward_type' => LotteryRewardType::GACHA,
            'source_type' => LotterySourceType::ORDER,
            'source_id' => '55',
            'is_done' => false,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/me/lottery/'.$entitlement->id.'/play?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_win', true)
            ->assertJsonPath('data.coupon.name', 'ガチャ当選クーポン');

        $entitlement->refresh();
        $this->assertTrue($entitlement->is_done);
        $this->assertSame(1, MemberCoupon::query()->where('member_id', $member->id)->count());
    }

    public function test_play_ではクーポン通知ジョブを即時投入しないこと(): void
    {
        Queue::fake();

        $project = $this->createProject();
        $user = User::factory()->create([
            'line_user_id' => self::LINE_USER_ID,
            'project_id' => $project->id,
        ]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'is_active' => true,
            'name' => '通知テストクーポン',
        ]);
        $this->enableGacha($project, $coupon);
        $entitlement = MemberLotteryEntitlement::query()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'reward_type' => LotteryRewardType::GACHA,
            'source_type' => LotterySourceType::ORDER,
            'source_id' => 'notify-play',
            'is_done' => false,
        ]);

        $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/me/lottery/'.$entitlement->id.'/play?project_key=default')
            ->assertStatus(200);

        Queue::assertNotPushed(SendCouponIssuedNotificationJob::class);
    }

    public function test_notify_win_でクーポン通知ジョブが投入されること(): void
    {
        Queue::fake();

        $project = $this->createProject();
        $user = User::factory()->create([
            'line_user_id' => self::LINE_USER_ID,
            'project_id' => $project->id,
        ]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'is_active' => true]);
        $this->enableGacha($project, $coupon);
        $entitlement = MemberLotteryEntitlement::query()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'reward_type' => LotteryRewardType::GACHA,
            'source_type' => LotterySourceType::ORDER,
            'source_id' => 'notify-win',
            'is_done' => false,
        ]);
        resolve(LotteryPlayService::class)->play($member, (int) $entitlement->id);
        $entitlement->refresh();

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/me/lottery/'.$entitlement->id.'/notify-win?project_key=default');

        $response->assertStatus(200)->assertJsonPath('success', true);
        Queue::assertPushed(SendCouponIssuedNotificationJob::class, function (SendCouponIssuedNotificationJob $job) use ($entitlement) {
            return $job->memberCouponId === (int) $entitlement->member_coupon_id;
        });
    }

    public function test_play_ハズレはクーポン発行せずis_win_falseになること(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create([
            'line_user_id' => self::LINE_USER_ID,
            'project_id' => $project->id,
        ]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        ProjectLotterySetting::query()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'gacha_enabled' => true,
                'scratch_enabled' => false,
                'grant_trigger' => LotteryGrantTrigger::EVERY_PURCHASE,
                'min_amount' => null,
                'entitlement_expires_hours' => null,
            ]
        );
        LotteryPrize::query()->create([
            'project_id' => $project->id,
            'reward_type' => LotteryRewardType::GACHA,
            'coupon_id' => null,
            'is_lose' => true,
            'probability' => 100,
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $entitlement = MemberLotteryEntitlement::query()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'reward_type' => LotteryRewardType::GACHA,
            'source_type' => LotterySourceType::ORDER,
            'source_id' => 'lose-1',
            'is_done' => false,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/me/lottery/'.$entitlement->id.'/play?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_win', false)
            ->assertJsonPath('data.coupon', null);

        $entitlement->refresh();
        $this->assertTrue($entitlement->is_done);
        $this->assertNull($entitlement->member_coupon_id);
        $this->assertSame(0, MemberCoupon::query()->where('member_id', $member->id)->count());
    }

    public function test_play_二重プレイは409になること(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create([
            'line_user_id' => self::LINE_USER_ID,
            'project_id' => $project->id,
        ]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'is_active' => true]);
        $this->enableGacha($project, $coupon);
        $entitlement = MemberLotteryEntitlement::query()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'reward_type' => LotteryRewardType::GACHA,
            'source_type' => LotterySourceType::ORDER,
            'source_id' => '77',
            'is_done' => false,
        ]);

        resolve(LotteryPlayService::class)->play($member, (int) $entitlement->id);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->post('/api/members/me/lottery/'.$entitlement->id.'/play?project_key=default');

        $response->assertStatus(409);
    }

    public function test_revoke_未消化権利を削除すること(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        MemberLotteryEntitlement::query()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'reward_type' => LotteryRewardType::GACHA,
            'source_type' => LotterySourceType::ORDER,
            'source_id' => '88',
            'is_done' => false,
        ]);

        resolve(LotteryEntitlementService::class)->revokePendingForSource(
            $member,
            LotterySourceType::ORDER,
            '88',
        );

        $this->assertSame(0, MemberLotteryEntitlement::query()->where('member_id', $member->id)->count());
    }

    public function test_revoke_当選済み未使用クーポンを回収すること(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'is_active' => true]);
        $this->enableGacha($project, $coupon);
        $entitlement = MemberLotteryEntitlement::query()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'reward_type' => LotteryRewardType::GACHA,
            'source_type' => LotterySourceType::ORDER,
            'source_id' => 'cancel-win',
            'is_done' => false,
        ]);
        resolve(LotteryPlayService::class)->play($member, (int) $entitlement->id);
        $this->assertSame(1, MemberCoupon::query()->where('member_id', $member->id)->count());

        resolve(LotteryEntitlementService::class)->revokePendingForSource(
            $member,
            LotterySourceType::ORDER,
            'cancel-win',
        );

        $this->assertSame(0, MemberCoupon::query()->where('member_id', $member->id)->count());
        $entitlement->refresh();
        $this->assertTrue($entitlement->is_done);
        $this->assertNull($entitlement->member_coupon_id);
    }

    public function test_revoke_使用済みクーポンは回収しないこと(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'is_active' => true]);
        $this->enableGacha($project, $coupon);
        $entitlement = MemberLotteryEntitlement::query()->create([
            'project_id' => $project->id,
            'member_id' => $member->id,
            'reward_type' => LotteryRewardType::GACHA,
            'source_type' => LotterySourceType::ORDER,
            'source_id' => 'cancel-used',
            'is_done' => false,
        ]);
        resolve(LotteryPlayService::class)->play($member, (int) $entitlement->id);
        $memberCoupon = MemberCoupon::query()->where('member_id', $member->id)->first();
        $this->assertNotNull($memberCoupon);
        $memberCoupon->status = MemberCoupon::STATUS_USED;
        $memberCoupon->used_at = now();
        $memberCoupon->save();

        resolve(LotteryEntitlementService::class)->revokePendingForSource(
            $member,
            LotterySourceType::ORDER,
            'cancel-used',
        );

        $this->assertSame(1, MemberCoupon::query()->where('member_id', $member->id)->count());
        $entitlement->refresh();
        $this->assertSame((int) $memberCoupon->id, (int) $entitlement->member_coupon_id);
    }

    public function test_wallet_summaryを取得できること(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create([
            'line_user_id' => self::LINE_USER_ID,
            'project_id' => $project->id,
        ]);
        Member::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'points' => 120,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => self::LINE_USER_ID])
            ->get('/api/members/me/wallet-summary?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('points', 120)
            ->assertJsonStructure(['points', 'stamps_total', 'pending_lottery_count', 'updated_at']);
    }
}
