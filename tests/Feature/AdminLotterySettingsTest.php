<?php

namespace Tests\Feature;

use App\Enums\LotteryGrantTrigger;
use App\Enums\LotteryRewardType;
use App\Models\AdminUser;
use App\Models\Coupon;
use App\Models\LotteryPrize;
use App\Models\Project;
use App\Models\Settings\ProjectLotterySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLotterySettingsTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
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
            ->assertSee('ガチャ・スクラッチ設定')
            ->assertSee('権利の付与タイミング');
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
}
