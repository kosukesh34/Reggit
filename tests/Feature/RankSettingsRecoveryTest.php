<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\MemberRankSettingStore;
use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\Settings\ProjectMemberPointsSetting;
use App\Models\SmaregiMemberRankSync;
use App\Models\SmaregiRankSettingsSync;
use App\Models\User;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use App\Services\Smaregi\SmaregiCustomerRankSyncService;
use App\Services\Smaregi\SmaregiRankSettingsRecoveryService;
use App\UseCases\Admin\Settings\Data\UpdateRankSettingsInput;
use App\UseCases\Admin\Settings\UpdateRankSettingsUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RankSettingsRecoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_worker_does_not_replace_new_settings_and_reopens_completed_request(): void
    {
        $project = Project::factory()->create();
        $projectId = (int) $project->id;
        app(MemberRankSettingStore::class)->setMemberRankSettings([['key' => 'bronze', 'label' => 'Old', 'min_points' => 0, 'is_active' => true]], $projectId);
        $this->mock(SmaregiCustomerRankSyncService::class, function ($mock) use ($projectId): void {
            $mock->shouldReceive('hasSmaregiContract')->andReturn(true);
            $mock->shouldReceive('sync')->once()->andReturnUsing(function () use ($projectId): array {
                app(MemberRankSettingStore::class)->setMemberRankSettings([['key' => 'bronze', 'label' => 'New', 'min_points' => 0, 'is_active' => true]], $projectId);
                app(SmaregiRankSettingsRecoveryService::class)->record($projectId);
                SmaregiRankSettingsSync::whereKey($projectId)->update(['delivered_at' => now()]);

                return ['bronze' => 'old-id'];
            });
        });
        $recovery = app(SmaregiRankSettingsRecoveryService::class);
        $recovery->record($projectId);

        $this->assertFalse($recovery->retryPending($projectId));

        $rows = app(MemberRankSettingStore::class)->getMemberRankSettings($projectId);
        $this->assertSame('New', $rows[0]['label']);
        $this->assertNotSame('old-id', $rows[0]['smaregi_rank_id'] ?? null);
        $this->assertNull(SmaregiRankSettingsSync::findOrFail($projectId)->delivered_at);
    }

    public function test_old_worker_repair_invalidates_new_worker_ack_during_member_fanout(): void
    {
        $projectId = (int) Project::factory()->create()->id;
        app(MemberRankSettingStore::class)->setMemberRankSettings([['key' => 'bronze', 'label' => 'Bronze', 'min_points' => 0, 'is_active' => true]], $projectId);
        $calls = 0;
        $this->mock(SmaregiCustomerRankSyncService::class, function ($mock) use (&$calls): void {
            $mock->shouldReceive('hasSmaregiContract')->andReturn(true);
            $mock->shouldReceive('sync')->twice()->andReturnUsing(function () use (&$calls): array {
                if (++$calls === 1) {
                    \Fiber::suspend();
                }

                return ['bronze' => 'remote-id'];
            });
        });
        $old = null;
        $this->mock(SmaregiCustomerRankAssignmentService::class, function ($mock) use (&$old): void {
            $mock->shouldReceive('syncPointRatesForProject')->once()->withArgs(fn ($id, $immediate) => $immediate === false)
                ->andReturnUsing(function () use (&$old): void {
                    $old->resume();
                });
        });
        $recovery = app(SmaregiRankSettingsRecoveryService::class);
        $recovery->record($projectId);
        $old = new \Fiber(fn () => $recovery->retryPending($projectId));
        $old->start();
        $this->travel(6)->minutes();
        $recovery->record($projectId);

        $this->assertFalse($recovery->retryPending($projectId));

        $this->assertTrue($old->isTerminated());
        $this->assertFalse($old->getReturn());
        $request = SmaregiRankSettingsSync::findOrFail($projectId);
        $this->assertNull($request->delivered_at);
        $this->assertNull($request->lease_token);
    }

    public function test_settings_write_failure_rolls_back_rank_rows_and_sync_request(): void
    {
        $projectId = (int) Project::factory()->create()->id;
        app(MemberRankSettingStore::class)->setMemberRankSettings([['key' => 'bronze', 'label' => 'Original', 'min_points' => 0, 'is_active' => true]], $projectId);
        app(ShopSettingStore::class)->setValue('points_valid_days', 365, 'integer', $projectId);
        Event::listen('eloquent.saving: '.ProjectMemberPointsSetting::class, function (): void {
            throw new \RuntimeException('settings write failed');
        });
        $input = new UpdateRankSettingsInput($projectId, 'points', 'lifetime', 90, false,
            50, 90, 0, 500, [['key' => 'bronze', 'label' => 'Changed', 'min_points' => 0, 'is_active' => true]]);
        try {
            app(UpdateRankSettingsUseCase::class)->handle($input);
            $this->fail('Expected settings write failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('settings write failed', $exception->getMessage());
        }

        $this->assertSame('Original', app(MemberRankSettingStore::class)->getMemberRankSettings($projectId)[0]['label']);
        $this->assertSame(365, app(MemberRankSettingStore::class)->getPointsValidDays($projectId));
        $this->assertDatabaseMissing('smaregi_rank_settings_syncs', ['project_id' => $projectId]);
    }

    public function test_contract_change_during_http_does_not_save_old_rank_ids(): void
    {
        $projectId = (int) Project::factory()->create()->id;
        app(MemberRankSettingStore::class)->setMemberRankSettings([['key' => 'bronze', 'label' => 'Bronze', 'min_points' => 0, 'is_active' => true]], $projectId);
        $contract = 'original';
        $this->mock(SmaregiApiService::class, function ($mock) use (&$contract): void {
            $mock->shouldReceive('getContractIdForProject')->andReturnUsing(function () use (&$contract): string {
                return $contract;
            });
        });
        $this->mock(SmaregiCustomerRankSyncService::class, function ($mock) use (&$contract): void {
            $mock->shouldReceive('hasSmaregiContract')->andReturn(true);
            $mock->shouldReceive('sync')->once()->andReturnUsing(function () use (&$contract): array {
                $contract = 'changed';

                return ['bronze' => 'original-contract-rank'];
            });
        });
        $recovery = app(SmaregiRankSettingsRecoveryService::class);
        $recovery->record($projectId);

        $this->assertFalse($recovery->retryPending($projectId));

        $rows = app(MemberRankSettingStore::class)->getMemberRankSettings($projectId);
        $this->assertNotSame('original-contract-rank', $rows[0]['smaregi_rank_id'] ?? null);
        $this->assertNull(SmaregiRankSettingsSync::findOrFail($projectId)->delivered_at);
    }

    public function test_contract_change_during_member_fanout_keeps_request_pending(): void
    {
        $projectId = (int) Project::factory()->create()->id;
        app(MemberRankSettingStore::class)->setMemberRankSettings([['key' => 'bronze', 'label' => 'Bronze', 'min_points' => 0, 'is_active' => true]], $projectId);
        $contract = 'original';
        $this->mock(SmaregiApiService::class, function ($mock) use (&$contract): void {
            $mock->shouldReceive('getContractIdForProject')->andReturnUsing(function () use (&$contract): string {
                return $contract;
            });
        });
        $this->mock(SmaregiCustomerRankSyncService::class, function ($mock): void {
            $mock->shouldReceive('hasSmaregiContract')->andReturn(true);
            $mock->shouldReceive('sync')->once()->andReturn(['bronze' => 'remote']);
        });
        $this->mock(SmaregiCustomerRankAssignmentService::class, function ($mock) use (&$contract): void {
            $mock->shouldReceive('syncPointRatesForProject')->once()->andReturnUsing(function () use (&$contract): void {
                $contract = 'changed';
            });
        });
        $recovery = app(SmaregiRankSettingsRecoveryService::class);
        $recovery->record($projectId);

        $this->assertFalse($recovery->retryPending($projectId));
        $this->assertNull(SmaregiRankSettingsSync::findOrFail($projectId)->delivered_at);
    }

    public function test_member_request_failure_keeps_project_request_for_retry(): void
    {
        $projectId = (int) Project::factory()->create()->id;
        app(MemberRankSettingStore::class)->setMemberRankSettings([['key' => 'bronze', 'label' => 'Bronze', 'min_points' => 0, 'is_active' => true]], $projectId);
        $this->mock(SmaregiCustomerRankSyncService::class, function ($mock): void {
            $mock->shouldReceive('hasSmaregiContract')->andReturn(true);
            $mock->shouldReceive('sync')->twice()->andReturn(['bronze' => 'remote']);
        });
        $calls = 0;
        $this->mock(SmaregiCustomerRankAssignmentService::class, function ($mock) use (&$calls): void {
            $mock->shouldReceive('syncPointRatesForProject')->twice()->andReturnUsing(function () use (&$calls): void {
                if (++$calls === 1) {
                    throw new \RuntimeException('member request write failed');
                }
            });
        });
        $recovery = app(SmaregiRankSettingsRecoveryService::class);
        $recovery->record($projectId);
        $this->assertFalse($recovery->retryPending($projectId));
        $this->assertNull(SmaregiRankSettingsSync::findOrFail($projectId)->delivered_at);
        $this->travel(61)->seconds();
        $this->assertTrue($recovery->retryPending($projectId));
        $this->assertNotNull(SmaregiRankSettingsSync::findOrFail($projectId)->delivered_at);
    }

    public function test_outer_rollback_discards_settings_request_and_external_sync(): void
    {
        $projectId = (int) Project::factory()->create()->id;
        app(MemberRankSettingStore::class)->setMemberRankSettings([['key' => 'bronze', 'label' => 'Original', 'min_points' => 0, 'is_active' => true]], $projectId);
        $this->mock(SmaregiCustomerRankSyncService::class, function ($mock): void {
            $mock->shouldReceive('hasSmaregiContract')->never();
            $mock->shouldReceive('sync')->never();
        });
        $input = new UpdateRankSettingsInput($projectId, 'points', 'lifetime', 90, false,
            50, 90, 0, 500, [['key' => 'bronze', 'label' => 'Changed', 'min_points' => 0, 'is_active' => true]]);
        try {
            DB::transaction(function () use ($input): void {
                app(UpdateRankSettingsUseCase::class)->handle($input);
                throw new \RuntimeException('outer rollback');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('outer rollback', $exception->getMessage());
        }

        $this->assertSame('Original', app(MemberRankSettingStore::class)->getMemberRankSettings($projectId)[0]['label']);
        $this->assertDatabaseMissing('smaregi_rank_settings_syncs', ['project_id' => $projectId]);
    }

    public function test_partial_member_request_writes_are_completed_on_retry(): void
    {
        $projectId = (int) Project::factory()->create()->id;
        app(MemberRankSettingStore::class)->setMemberRankSettings([['key' => 'bronze', 'label' => 'Bronze', 'min_points' => 0, 'is_active' => true]], $projectId);
        $members = [];
        foreach (['customer-one', 'customer-two'] as $customer) {
            $user = User::factory()->create(['project_id' => $projectId]);
            $members[] = Member::factory()->create(['project_id' => $projectId, 'user_id' => $user->id,
                'smaregi_customer_id' => $customer, 'rank' => 'bronze']);
        }
        $this->mock(SmaregiApiService::class, function ($mock): void {
            $mock->shouldReceive('getContractIdForProject')->andReturn('contract');
            $mock->shouldReceive('updateCustomer')->never();
        });
        $this->mock(SmaregiCustomerRankSyncService::class, function ($mock): void {
            $mock->shouldReceive('hasSmaregiContract')->andReturn(true);
            $mock->shouldReceive('sync')->twice()->andReturn(['bronze' => 'remote']);
        });
        $writes = 0;
        $inject = true;
        DB::connection()->beforeExecuting(function (string $sql) use (&$writes, &$inject): void {
            if ($inject && str_starts_with($sql, 'insert into `smaregi_member_rank_syncs`') && ++$writes === 2) {
                throw new \RuntimeException('second member request failed');
            }
        });
        $recovery = app(SmaregiRankSettingsRecoveryService::class);
        $recovery->record($projectId);
        $this->assertFalse($recovery->retryPending($projectId));
        $this->assertSame(2, $writes);
        $this->assertDatabaseHas('smaregi_member_rank_syncs', ['member_id' => $members[0]->id]);
        $this->assertDatabaseMissing('smaregi_member_rank_syncs', ['member_id' => $members[1]->id]);
        $this->assertNull(SmaregiRankSettingsSync::findOrFail($projectId)->delivered_at);
        $inject = false;
        $this->travel(61)->seconds();
        $this->assertTrue($recovery->retryPending($projectId));
        $this->assertSame(2, SmaregiMemberRankSync::where('project_id', $projectId)->count());
        $this->assertNotNull(SmaregiRankSettingsSync::findOrFail($projectId)->delivered_at);
    }

    public function test_saved_rank_settings_are_retried_after_rank_master_failure(): void
    {
        $project = Project::factory()->create();
        $calls = 0;
        $this->mock(SmaregiCustomerRankSyncService::class, function ($mock) use (&$calls): void {
            $mock->shouldReceive('hasSmaregiContract')->andReturn(true);
            $mock->shouldReceive('sync')->andReturnUsing(function () use (&$calls): array {
                $calls++;
                if ($calls === 1) {
                    throw new \RuntimeException('rank master unavailable');
                }

                return ['bronze' => 'remote-bronze'];
            });
        });
        $input = new UpdateRankSettingsInput(
            (int) $project->id, 'points', 'lifetime', 365, false,
            100, 365, 0, 500,
            [['key' => 'bronze', 'label' => 'Bronze', 'min_points' => 0,
                'is_active' => true, 'point_earn_rate' => 50]],
        );
        try {
            app(UpdateRankSettingsUseCase::class)->handle($input);
        } catch (\RuntimeException $exception) {
            $this->assertSame('rank master unavailable', $exception->getMessage());
        }
        $this->assertSame(50, app(MemberRankSettingStore::class)->getPointEarnRateForRank((int) $project->id, 'bronze'));

        $this->travel(61)->seconds();
        $this->artisan('smaregi:retry-member-ranks')->assertSuccessful();

        $this->assertSame(2, $calls, 'Saved settings must retain a request to retry rank master synchronization.');
        $rows = app(MemberRankSettingStore::class)->getMemberRankSettings((int) $project->id);
        $this->assertSame('remote-bronze', $rows[0]['smaregi_rank_id']);
    }
}
