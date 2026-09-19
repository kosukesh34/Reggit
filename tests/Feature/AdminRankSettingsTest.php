<?php

namespace Tests\Feature;

use App\Enums\RankAggregationType;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\AdminUser;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRankSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ランク設定画面に機能トグルと期間設定があること(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.ranks', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertSee('ポイント機能を利用する', false)
            ->assertSee('スタンプ機能を利用する', false)
            ->assertSee('取引連動スタンプを利用する', false)
            ->assertSee('name="rank_aggregation_type"', false)
            ->assertSee('集計期間（日）', false)
            ->assertSee('name="ranks[0][stamp_earn_per_amount]"', false)
            ->assertSee('会員が現在持っているスタンプラリー', false)
            ->assertDontSee('付与先スタンプカード', false);
    }

    public function test_ランク設定画面で会員証デザインを選べること(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.ranks', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertSee('ranks-design-picker', false)
            ->assertSee('name="member_card_design"', false)
            ->assertSee('value="compact"', false)
            ->assertSee('デジタルパス', false)
            ->assertSee('liff-membership-preview', false)
            ->assertSee('MEMBER STATUS', false)
            ->assertSee('会員情報', false);
    }

    public function test_会員証画面の表示項目を会員証設定で変更できること(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.ranks', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertSee('name="member_show_rank_progress"', false)
            ->assertSee('name="member_show_point_history"', false)
            ->assertSee('name="member_show_coupon_activity"', false);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.ranks.update', ['project_key' => $project->project_key]), [
                'rank_basis' => 'points',
                'rank_aggregation_type' => RankAggregationType::TOTAL,
                'rank_period_days' => 365,
                'point_earn_rate' => 100,
                'points_valid_days' => 365,
                'member_show_rank_progress' => '1',
                'member_show_point_history' => '0',
                'member_show_coupon_activity' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertTrue((bool) ShopSettingStore::getValue('member_show_rank_progress', false, $project->id));
        $this->assertFalse((bool) ShopSettingStore::getValue('member_show_point_history', true, $project->id));
        $this->assertFalse((bool) ShopSettingStore::getValue('member_show_coupon_activity', true, $project->id));
    }

    public function test_会員証デザイン保存が_lif_fタブ設定へ反映されること(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.ranks.update', ['project_key' => $project->project_key]), [
                'rank_basis' => 'points',
                'rank_aggregation_type' => RankAggregationType::TOTAL,
                'rank_period_days' => 365,
                'use_points' => '1',
                'use_stamps' => '1',
                'use_transaction_stamps' => '1',
                'point_earn_rate' => 100,
                'points_valid_days' => 365,
                'member_card_design' => 'compact',
                'member_card_display_type' => 'sheet',
                'member_card_code_type' => 'qrcode',
                'ranks' => [
                    [
                        'key' => 'bronze',
                        'label' => 'ブロンズ',
                        'min_points' => 0,
                        'is_active' => '1',
                        'order' => 1,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertSame('compact', ShopSettingStore::getMemberCardDesign((int) $project->id));
        $this->assertSame('sheet', ShopSettingStore::getMemberCardDisplayType((int) $project->id));
        $this->assertSame('qrcode', ShopSettingStore::getMemberCardCodeType((int) $project->id));

        $tabSettings = ShopSettingStore::getLiffTabSettings((int) $project->id);
        $this->assertSame('compact', $tabSettings['_global']['member_card_design'] ?? null);
        $this->assertSame('sheet', $tabSettings['_global']['member_card_display_type'] ?? null);
        $this->assertSame('qrcode', $tabSettings['_global']['member_card_code_type'] ?? null);
        $this->assertSame('simple', $tabSettings['_global']['app_customization']['membership']['template'] ?? null);
    }

    public function test_チケット会員証デザインがテンプレートへ同期されること(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.ranks.update', ['project_key' => $project->project_key]), [
                'rank_basis' => 'points',
                'rank_aggregation_type' => RankAggregationType::TOTAL,
                'rank_period_days' => 365,
                'use_points' => '1',
                'use_stamps' => '1',
                'use_transaction_stamps' => '1',
                'point_earn_rate' => 100,
                'points_valid_days' => 365,
                'member_card_design' => 'ticket',
                'ranks' => [
                    [
                        'key' => 'bronze',
                        'label' => 'ブロンズ',
                        'min_points' => 0,
                        'is_active' => '1',
                        'order' => 1,
                    ],
                ],
            ])
            ->assertRedirect();

        $tabSettings = ShopSettingStore::getLiffTabSettings((int) $project->id);
        $this->assertSame('ticket', $tabSettings['_global']['member_card_design'] ?? null);
        $this->assertSame('ticket', $tabSettings['_global']['app_customization']['membership']['template'] ?? null);
    }

    public function test_ランク設定に機能トグルと期間を保存できること(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.ranks.update', ['project_key' => $project->project_key]), [
                'rank_basis' => 'points',
                'rank_aggregation_type' => RankAggregationType::PERIOD,
                'rank_period_days' => 90,
                'is_rank_down_enabled' => '1',
                'use_points' => '1',
                'use_stamps' => '1',
                'use_transaction_stamps' => '1',
                'point_earn_rate' => 100,
                'points_valid_days' => 365,
                'points_per_order' => 0,
                'stamp_earn_per_amount' => 500,
                'ranks' => [
                    [
                        'key' => 'bronze',
                        'label' => 'ブロンズ',
                        'min_points' => 0,
                        'point_earn_rate' => 80,
                        'stamp_earn_per_amount' => 300,
                        'is_active' => '1',
                        'order' => 1,
                    ],
                    [
                        'key' => 'gold',
                        'label' => 'ゴールド',
                        'min_points' => 1000,
                        'point_earn_rate' => 50,
                        'stamp_earn_per_amount' => 200,
                        'is_active' => '1',
                        'order' => 2,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertTrue(ShopSettingStore::getUsePoints((int) $project->id));
        $this->assertTrue(ShopSettingStore::getUseStamps((int) $project->id));
        $this->assertTrue(ShopSettingStore::getUseTransactionStamps((int) $project->id));
        $this->assertSame(RankAggregationType::PERIOD, ShopSettingStore::getRankAggregationType((int) $project->id));
        $this->assertSame(90, ShopSettingStore::getRankPeriodDays((int) $project->id));
        $this->assertTrue(ShopSettingStore::getIsRankDownEnabled((int) $project->id));
        $this->assertSame(50, ShopSettingStore::getPointEarnRateForRank((int) $project->id, 'gold'));
        $this->assertSame(200, ShopSettingStore::getStampEarnPerAmountForRank((int) $project->id, 'gold'));
        $this->assertTrue(ShopSettingStore::isFeatureEnabled('feature_stamps_enabled', (int) $project->id));
    }

    public function test_スタンプ機能オフなら取引連動もオフになり機能フラグも落ちること(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.ranks.update', ['project_key' => $project->project_key]), [
                'rank_basis' => 'points',
                'rank_aggregation_type' => RankAggregationType::TOTAL,
                'rank_period_days' => 365,
                'use_points' => '1',
                'use_stamps' => '0',
                'use_transaction_stamps' => '1',
                'point_earn_rate' => 100,
                'points_valid_days' => 365,
            ])
            ->assertRedirect();

        $this->assertFalse(ShopSettingStore::getUseStamps((int) $project->id));
        $this->assertFalse(ShopSettingStore::getUseTransactionStamps((int) $project->id));
        $this->assertFalse(ShopSettingStore::isFeatureEnabled('feature_stamps_enabled', (int) $project->id));
    }

    public function test_ランク別付与率が未設定ならデフォルト値が保存されること(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.settings.ranks.update', ['project_key' => $project->project_key]), [
                'rank_basis' => 'points',
                'rank_aggregation_type' => RankAggregationType::TOTAL,
                'rank_period_days' => 365,
                'use_points' => '1',
                'use_stamps' => '1',
                'use_transaction_stamps' => '1',
                'point_earn_rate' => 120,
                'points_valid_days' => 365,
                'stamp_earn_per_amount' => 800,
                'ranks' => [
                    [
                        'key' => 'bronze',
                        'label' => 'ブロンズ',
                        'min_points' => 0,
                        'is_active' => '1',
                        'order' => 1,
                    ],
                ],
            ])
            ->assertRedirect();

        $ranks = ShopSettingStore::getMemberRankSettings((int) $project->id);
        $this->assertCount(1, $ranks);
        $this->assertSame(120, $ranks[0]['point_earn_rate']);
        $this->assertSame(800, $ranks[0]['stamp_earn_per_amount']);
        $this->assertSame(120, ShopSettingStore::getPointEarnRateForRank((int) $project->id, 'bronze'));
        $this->assertSame(800, ShopSettingStore::getStampEarnPerAmountForRank((int) $project->id, 'bronze'));
    }

    public function test_ランク設定画面にデフォルト付与率が表示されること(): void
    {
        $project = Project::factory()->create();
        $admin = AdminUser::factory()->create([
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $project->id,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.settings.ranks', ['project_key' => $project->project_key]))
            ->assertOk()
            ->assertSee('name="ranks[0][point_earn_rate]"', false)
            ->assertSee('value="100"', false);
    }
}
