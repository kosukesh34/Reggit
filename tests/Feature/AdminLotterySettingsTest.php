<?php

namespace Tests\Feature;

use App\Enums\LotteryGrantTrigger;
use App\Enums\LotteryRewardType;
use App\Models\AdminUser;
use App\Models\Coupon;
use App\Models\LotteryPrize;
use App\Models\Member;
use App\Models\MemberLotteryEntitlement;
use App\Models\Project;
use App\Models\Settings\ProjectLotterySetting;
use App\Services\Lottery\LotteryPlayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLotterySettingsTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true, 'plan' => 'premium']
        );
        if ($project->plan !== 'premium') {
            $project->update(['plan' => 'premium']);
        }

        return $project;
    }

    private function createAdmin(Project $project): AdminUser
    {
        return AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);
    }

    public function test_lottery_settings_画面にアクセスできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.lottery', ['project_key' => $project->project_key]));

        $response->assertStatus(200)
            ->assertSee('購入特典ゲーム')
            ->assertSee('プレイ権の付与条件')
            ->assertSee('対象となる最低購入金額')
            ->assertSee('実績サマリー')
            ->assertSee('景品別の当選数');
    }

    public function test_lottery_settings_実績件数が表示されること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'is_active' => true,
            'name' => '統計確認クーポン',
        ]);
        MemberLotteryEntitlement::query()->create([
            'project_id' => $project->id,
            'member_id' => Member::factory()->create(['project_id' => $project->id])->id,
            'reward_type' => LotteryRewardType::GACHA,
            'source_type' => 'order',
            'source_id' => 'stats-1',
            'is_done' => true,
            'done_at' => now(),
            'won_coupon_id' => $coupon->id,
            'member_coupon_id' => null,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.lottery', ['project_key' => $project->project_key]));

        $response->assertStatus(200)
            ->assertSee('統計確認クーポン')
            ->assertSee('当選');
    }

    public function test_lottery_settings_確率合計100で保存できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $couponA = Coupon::factory()->create(['project_id' => $project->id, 'is_active' => true, 'name' => '景品A']);
        $couponB = Coupon::factory()->create(['project_id' => $project->id, 'is_active' => true, 'name' => '景品B']);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.lottery.update', ['project_key' => $project->project_key]), [
                'gacha_enabled' => '1',
                'scratch_enabled' => '0',
                'grant_trigger' => LotteryGrantTrigger::EVERY_PURCHASE,
                'min_amount' => null,
                'entitlement_expires_hours' => 24,
                'prizes' => [
                    [
                        'reward_type' => LotteryRewardType::GACHA,
                        'coupon_id' => $couponA->id,
                        'probability' => 70,
                    ],
                    [
                        'reward_type' => LotteryRewardType::GACHA,
                        'coupon_id' => $couponB->id,
                        'probability' => 30,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.settings.lottery', ['project_key' => $project->project_key]));

        $settings = ProjectLotterySetting::query()->where('project_id', $project->id)->first();
        $this->assertNotNull($settings);
        $this->assertTrue($settings->gacha_enabled);
        $this->assertFalse($settings->scratch_enabled);
        $this->assertSame(24, $settings->entitlement_expires_hours);
        $this->assertSame(2, LotteryPrize::query()->where('project_id', $project->id)->count());
    }

    public function test_lottery_settings_ハズレ景品を保存できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'is_active' => true, 'name' => '景品A']);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.lottery.update', ['project_key' => $project->project_key]), [
                'gacha_enabled' => '1',
                'scratch_enabled' => '0',
                'grant_trigger' => LotteryGrantTrigger::EVERY_PURCHASE,
                'min_amount' => null,
                'entitlement_expires_hours' => null,
                'prizes' => [
                    [
                        'reward_type' => LotteryRewardType::GACHA,
                        'coupon_id' => $coupon->id,
                        'is_lose' => '0',
                        'probability' => 70,
                    ],
                    [
                        'reward_type' => LotteryRewardType::GACHA,
                        'coupon_id' => 'lose',
                        'is_lose' => '1',
                        'probability' => 30,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.settings.lottery', ['project_key' => $project->project_key]));

        $lose = LotteryPrize::query()
            ->where('project_id', $project->id)
            ->where('is_lose', true)
            ->first();
        $this->assertNotNull($lose);
        $this->assertNull($lose->coupon_id);
        $this->assertSame(30, $lose->probability);
        $this->assertSame(2, LotteryPrize::query()->where('project_id', $project->id)->count());
    }

    public function test_lottery_settings_確率合計が100でないと保存できないこと(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'is_active' => true]);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.settings.lottery', ['project_key' => $project->project_key]))
            ->post(route('admin.settings.lottery.update', ['project_key' => $project->project_key]), [
                'gacha_enabled' => '1',
                'scratch_enabled' => '0',
                'grant_trigger' => LotteryGrantTrigger::EVERY_PURCHASE,
                'prizes' => [
                    [
                        'reward_type' => LotteryRewardType::GACHA,
                        'coupon_id' => $coupon->id,
                        'probability' => 40,
                    ],
                ],
            ]);

        $response->assertRedirect(route('admin.settings.lottery', ['project_key' => $project->project_key]));
        $response->assertSessionHasErrors();
        $this->assertSame(0, LotteryPrize::query()->where('project_id', $project->id)->count());
    }

    public function test_lottery_settings_購入金額条件では最低購入金額が必須であること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);

        $response = $this->actingAs($admin, 'admin')
            ->from(route('admin.settings.lottery', ['project_key' => $project->project_key]))
            ->post(route('admin.settings.lottery.update', ['project_key' => $project->project_key]), [
                'gacha_enabled' => '0',
                'scratch_enabled' => '0',
                'grant_trigger' => LotteryGrantTrigger::MIN_AMOUNT,
                'min_amount' => null,
                'prizes' => [],
            ]);

        $response->assertSessionHasErrors('min_amount');
    }

    public function test_lottery_state_購入特典の条件をネイティブ向けに返すこと(): void
    {
        $project = $this->createProject();
        $member = Member::factory()->create(['project_id' => $project->id]);
        ProjectLotterySetting::query()->updateOrCreate(
            ['project_id' => $project->id],
            [
                'gacha_enabled' => true,
                'scratch_enabled' => false,
                'grant_trigger' => LotteryGrantTrigger::MIN_AMOUNT,
                'min_amount' => 3000,
                'entitlement_expires_hours' => 24,
            ]
        );

        $state = resolve(LotteryPlayService::class)->getMemberLotteryState($member);

        $this->assertSame(LotteryGrantTrigger::MIN_AMOUNT, $state['eligibility']['grant_trigger']);
        $this->assertSame(3000.0, $state['eligibility']['min_amount']);
        $this->assertSame(24, $state['eligibility']['entitlement_expires_hours']);
    }

    public function test_ルーレット設定と景品を管理画面から保存できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project);
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.lottery.update', ['project_key' => $project->project_key]), [
                'gacha_enabled' => '0',
                'scratch_enabled' => '0',
                'roulette_enabled' => '1',
                'grant_trigger' => LotteryGrantTrigger::MIN_AMOUNT,
                'min_amount' => 3000,
                'prizes' => [[
                    'reward_type' => 'roulette',
                    'coupon_id' => $coupon->id,
                    'probability' => 100,
                ]],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('project_lottery_settings', [
            'project_id' => $project->id,
            'roulette_enabled' => true,
            'grant_trigger' => LotteryGrantTrigger::MIN_AMOUNT,
            'min_amount' => 3000,
        ]);
        $this->assertDatabaseHas('lottery_prizes', [
            'project_id' => $project->id,
            'reward_type' => 'roulette',
            'coupon_id' => $coupon->id,
            'probability' => 100,
        ]);
    }
}
