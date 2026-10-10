<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\SmaregiMemberRankSync;
use App\Models\SmaregiRankSettingsSync;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use App\UseCases\Admin\Settings\Data\UpdateRankSettingsInput;
use App\UseCases\Admin\Settings\UpdateRankSettingsUseCase;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class SmaregiRankSettingsRetryTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    private function api(): MockInterface
    {
        $api = \Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturnUsing(fn ($id) => 'contract-'.$id);
        $api->shouldReceive('clearLastErrorDetail')->andReturnNull();
        $api->shouldReceive('getLastErrorDetail')->andReturnNull();
        app()->instance(SmaregiApiService::class, $api);

        return $api;
    }

    private function save(Project $project, int $rate = 50, string $label = 'Gold'): bool
    {
        return app(UpdateRankSettingsUseCase::class)->handle(new UpdateRankSettingsInput(
            (int) $project->id, 'points', 'total', 365, false, 100, 365, 0, 0,
            [['key' => 'gold', 'label' => $label, 'min_points' => 0, 'is_active' => true, 'point_earn_rate' => $rate]],
        ));
    }

    public function test_setting_edit_preserves_mapping_committed_after_its_repeatable_read_snapshot(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL repeatable-read snapshots.');
        }
        $project = Project::factory()->create();
        ShopSettingStore::setMemberRankSettings([['key' => 'gold', 'label' => 'Gold', 'smaregi_rank_id' => null]], $project->id);
        $api = $this->api();
        $api->shouldReceive('getCustomerRanks')->once()->andReturn([['rank' => '7', 'name' => 'Gold']]);
        $api->shouldReceive('updateCustomerRank')->once()->with('contract-'.$project->id, '7', \Mockery::on(fn ($body) => $body['name'] === 'Renamed Gold'))->andReturn([]);
        $api->shouldNotReceive('createCustomerRank');
        $api->shouldNotReceive('deleteCustomerRank');
        $default = DB::getDefaultConnection();
        config(['database.connections.rank_mapping_writer' => config('database.connections.'.$default)]);
        $writer = DB::connection('rank_mapping_writer');
        DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        DB::beginTransaction();
        try {
            $this->assertNull(ShopSettingStore::getMemberRankSettings($project->id)[0]['smaregi_rank_id']);
            $writer->table('project_member_rank_settings')->where('project_id', $project->id)->update([
                'member_rank_settings' => json_encode([['key' => 'gold', 'label' => 'Gold', 'smaregi_rank_id' => '7']], JSON_THROW_ON_ERROR),
            ]);
            $this->assertNull(ShopSettingStore::getMemberRankSettings($project->id)[0]['smaregi_rank_id'], 'The outer transaction retains its old snapshot.');
            $this->save($project, 25, 'Renamed Gold');
            $this->assertSame('7', ShopSettingStore::getMemberRankSettings($project->id)[0]['smaregi_rank_id']);
            DB::commit();
            $this->assertDatabaseCount('smaregi_rank_settings_syncs', 0);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('rank_mapping_writer');
        }
    }

    public function test_old_worker_finishing_after_new_worker_ack_schedules_latest_reconciliation(): void
    {
        $project = Project::factory()->create();
        $api = $this->api();
        $api->shouldReceive('getCustomerRanks')->times(3)->andReturn([], [['rank' => '7', 'name' => 'Gold']], [['rank' => '7', 'name' => 'Gold']]);
        $api->shouldReceive('createCustomerRank')->once()->andReturnUsing(function () use ($project): array {
            SmaregiRankSettingsSync::whereKey($project->id)->update(['leased_until' => now()->subMinute()]);
            $this->assertTrue($this->save($project, 25));
            $this->assertDatabaseCount('smaregi_rank_settings_syncs', 0);

            return ['rank' => '7'];
        });
        $api->shouldReceive('updateCustomerRank')->twice()->with('contract-'.$project->id, '7', \Mockery::on(fn ($body) => $body['pointGivingUnitPrice'] === '25'))->andReturn([]);

        $this->assertFalse($this->save($project, 50));
        $this->assertSame(25, ShopSettingStore::getMemberRankSettings($project->id)[0]['point_earn_rate']);
        $this->assertDatabaseCount('smaregi_rank_settings_syncs', 1);
        $this->artisan('smaregi:retry-rank-settings')->assertSuccessful();
        $this->assertDatabaseCount('smaregi_rank_settings_syncs', 0);
    }

    public function test_cleared_settings_supersede_active_worker_without_restoring_old_rows(): void
    {
        $project = Project::factory()->create();
        $api = $this->api();
        $api->shouldReceive('getCustomerRanks')->once()->andReturn([]);
        $api->shouldReceive('createCustomerRank')->once()->andReturnUsing(function () use ($project): array {
            app(UpdateRankSettingsUseCase::class)->handle(new UpdateRankSettingsInput(
                (int) $project->id, 'points', 'total', 365, false, 100, 365, 0, 0, [],
            ));

            return ['rank' => '7'];
        });

        $this->assertFalse($this->save($project));
        $this->assertSame([], ShopSettingStore::getValue('member_rank_settings', null, $project->id));
        $this->artisan('smaregi:retry-rank-settings')->assertSuccessful();
        $this->assertSame([], ShopSettingStore::getValue('member_rank_settings', null, $project->id));
        $this->assertDatabaseCount('smaregi_rank_settings_syncs', 0);
    }

    public function test_missing_contract_retains_pending_work_with_hourly_backoff(): void
    {
        $this->travelTo(now()->startOfSecond());
        $project = Project::factory()->create();
        ShopSettingStore::setMemberRankSettings([['key' => 'gold', 'label' => 'Gold']], $project->id);
        SmaregiRankSettingsSync::create(['project_id' => $project->id, 'request_token' => 'request', 'retry_at' => now()]);
        $api = \Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn(null);
        $api->shouldNotReceive('getCustomerRanks');
        app()->instance(SmaregiApiService::class, $api);

        $this->artisan('smaregi:retry-rank-settings')->assertSuccessful();
        $pending = SmaregiRankSettingsSync::findOrFail($project->id);
        $this->assertTrue($pending->retry_at->equalTo(now()->addHour()));
        $this->assertNull($pending->lease_token);
    }

    public function test_failed_catalog_recovers_mapping_and_materializes_all_members_before_ack(): void
    {
        $project = Project::factory()->create();
        $members = Member::factory()->count(2)->create(['project_id' => $project->id, 'rank' => 'gold', 'smaregi_customer_id' => 'customer']);
        $api = $this->api();
        $api->shouldReceive('getCustomerRanks')->twice()->andReturn(null, []);
        $api->shouldReceive('createCustomerRank')->once()->andReturn(['rank' => '7']);
        $api->shouldReceive('updateCustomer')->twice()->with('contract-'.$project->id, 'customer', \Mockery::on(fn ($body) => $body['rank'] === '7' && $body['pointGivingUnitPrice'] === '50'))->andReturn([]);

        $this->assertFalse($this->save($project));
        $this->assertDatabaseCount('smaregi_rank_settings_syncs', 1);
        $this->assertDatabaseCount('smaregi_member_rank_syncs', 0);
        $this->assertSame(50, ShopSettingStore::getMemberRankSettings($project->id)[0]['point_earn_rate']);
        $this->travel(1)->minutes();
        $this->artisan('smaregi:retry-rank-settings')->assertSuccessful();
        $this->assertDatabaseCount('smaregi_rank_settings_syncs', 0);
        $this->assertDatabaseCount('smaregi_member_rank_syncs', 2);
        $this->assertSame('7', ShopSettingStore::getMemberRankSettings($project->id)[0]['smaregi_rank_id']);
        $this->artisan('smaregi:retry-member-ranks')->assertSuccessful();
        $this->assertDatabaseCount('smaregi_member_rank_syncs', 0);
    }

    public function test_outer_rollback_discards_settings_and_intent_without_remote_calls(): void
    {
        $project = Project::factory()->create();
        ShopSettingStore::setMemberRankSettings([['key' => 'bronze', 'label' => 'Bronze']], $project->id);
        $api = $this->api();
        $api->shouldNotReceive('getCustomerRanks');
        DB::beginTransaction();
        try {
            $this->save($project);
            $this->assertDatabaseCount('smaregi_rank_settings_syncs', 1);
        } finally {
            DB::rollBack();
        }
        $this->assertSame('bronze', ShopSettingStore::getMemberRankSettings($project->id)[0]['key']);
        $this->assertDatabaseCount('smaregi_rank_settings_syncs', 0);
    }

    public function test_old_worker_cannot_overwrite_new_settings_or_ack_new_request(): void
    {
        $project = Project::factory()->create();
        $api = $this->api();
        $api->shouldReceive('getCustomerRanks')->twice()->andReturn([], [['rank' => '7', 'name' => 'Gold']]);
        $api->shouldReceive('createCustomerRank')->once()->andReturnUsing(function () use ($project): array {
            $this->assertFalse($this->save($project, 25, 'New Gold'));

            return ['rank' => '7'];
        });
        // First creation cannot save mapping after a newer edit. A subsequent retry discovers by name.
        $api->shouldReceive('createCustomerRank')->once()->with('contract-'.$project->id, \Mockery::on(fn ($body) => $body['name'] === 'New Gold'))->andReturn(['rank' => '8']);
        $api->shouldReceive('deleteCustomerRank')->once()->with('contract-'.$project->id, '7')->andReturn([]);

        $this->assertFalse($this->save($project));
        $row = ShopSettingStore::getMemberRankSettings($project->id)[0];
        $this->assertSame('New Gold', $row['label']);
        $this->assertNull($row['smaregi_rank_id']);
        $this->assertDatabaseCount('smaregi_rank_settings_syncs', 1);
        $this->artisan('smaregi:retry-rank-settings')->assertSuccessful();
        $this->assertSame('8', ShopSettingStore::getMemberRankSettings($project->id)[0]['smaregi_rank_id']);
        $this->assertDatabaseCount('smaregi_rank_settings_syncs', 0);
    }

    public function test_interrupted_member_batch_rolls_back_mapping_and_every_member_intent(): void
    {
        $project = Project::factory()->create();
        $member = Member::factory()->create(['project_id' => $project->id, 'rank' => 'gold', 'smaregi_customer_id' => 'customer']);
        $api = $this->api();
        $api->shouldReceive('getCustomerRanks')->twice()->andReturn([], [['rank' => '7', 'name' => 'Gold']]);
        $api->shouldReceive('createCustomerRank')->once()->andReturn(['rank' => '7']);
        $api->shouldReceive('updateCustomerRank')->once()->andReturn([]);
        $api->shouldNotReceive('updateCustomer');
        $real = app(SmaregiCustomerRankAssignmentService::class);
        $calls = 0;
        $this->mock(SmaregiCustomerRankAssignmentService::class, function ($mock) use ($real, &$calls): void {
            $mock->shouldReceive('requestPointRatesForProject')->twice()->andReturnUsing(function ($id) use ($real, &$calls): void {
                $real->requestPointRatesForProject($id);
                if (++$calls === 1) {
                    throw new \RuntimeException('interrupted after member intents');
                }
            });
        });

        $this->assertFalse($this->save($project));
        $this->assertNull(ShopSettingStore::getMemberRankSettings($project->id)[0]['smaregi_rank_id']);
        $this->assertDatabaseCount('smaregi_member_rank_syncs', 0);
        $this->assertDatabaseCount('smaregi_rank_settings_syncs', 1);
        $this->travel(1)->minutes();
        $this->artisan('smaregi:retry-rank-settings')->assertSuccessful();
        $this->assertDatabaseCount('smaregi_member_rank_syncs', 1);
        $this->assertDatabaseCount('smaregi_rank_settings_syncs', 0);
        $this->assertSame('7', ShopSettingStore::getMemberRankSettings($project->id)[0]['smaregi_rank_id']);
    }

    public function test_expired_lease_recovers_and_replacement_member_intent_preserves_active_lease(): void
    {
        $project = Project::factory()->create();
        $member = Member::factory()->create(['project_id' => $project->id, 'rank' => 'gold', 'smaregi_customer_id' => 'customer']);
        ShopSettingStore::setMemberRankSettings([['key' => 'gold', 'label' => 'Gold', 'point_earn_rate' => 25]], $project->id);
        SmaregiRankSettingsSync::create(['project_id' => $project->id, 'request_token' => 'request', 'retry_at' => now(), 'leased_until' => now()->subMinute(), 'lease_token' => 'expired']);
        SmaregiMemberRankSync::create(['member_id' => $member->id, 'project_id' => $project->id, 'request_token' => 'old', 'retry_at' => now(), 'leased_until' => now()->addMinutes(5), 'lease_token' => 'active']);
        $api = $this->api();
        $api->shouldReceive('getCustomerRanks')->once()->andReturn([]);
        $api->shouldReceive('createCustomerRank')->once()->andReturn(['rank' => '7']);
        $api->shouldNotReceive('updateCustomer');
        $other = Project::factory()->create();
        AdminContext::setCurrentProject($other);

        $this->artisan('smaregi:retry-rank-settings')->assertSuccessful();

        $this->assertSame($other->id, AdminContext::getCurrentProject()->id);
        $pending = SmaregiMemberRankSync::findOrFail($member->id);
        $this->assertNotSame('old', $pending->request_token);
        $this->assertSame('active', $pending->lease_token);
        $this->assertTrue($pending->leased_until->isFuture());
        $this->assertDatabaseCount('smaregi_rank_settings_syncs', 0);
    }

    public function test_failed_project_does_not_block_next_project(): void
    {
        $first = Project::factory()->create();
        $second = Project::factory()->create();
        foreach ([$first, $second] as $project) {
            ShopSettingStore::setMemberRankSettings([['key' => 'gold', 'label' => 'Gold']], $project->id);
            SmaregiRankSettingsSync::create(['project_id' => $project->id, 'request_token' => 'request', 'retry_at' => now()]);
        }
        $api = $this->api();
        $api->shouldReceive('getCustomerRanks')->once()->with('contract-'.$first->id, \Mockery::any())->andReturn(null);
        $api->shouldReceive('getCustomerRanks')->once()->with('contract-'.$second->id, \Mockery::any())->andReturn([]);
        $api->shouldReceive('createCustomerRank')->once()->andReturn(['rank' => '7']);

        $this->artisan('smaregi:retry-rank-settings')->assertFailed();
        $this->assertDatabaseHas('smaregi_rank_settings_syncs', ['project_id' => $first->id, 'lease_token' => null]);
        $this->assertDatabaseMissing('smaregi_rank_settings_syncs', ['project_id' => $second->id]);
    }
}
