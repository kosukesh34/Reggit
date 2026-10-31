<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\MemberRankSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\SmaregiMemberRankSync;
use App\Services\Admin\MemberAdminService;
use App\Services\MemberRankCalculator;
use App\Services\MemberRankSyncService;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerRankAssignmentService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Tests\TestCase;

class SmaregiMemberRankRetryTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    private function member(string $rank = 'gold'): Member
    {
        $project = Project::factory()->create();
        app(MemberRankSettingStore::class)->setMemberRankSettings([
            ['key' => 'bronze', 'label' => 'Bronze', 'min_points' => 0, 'is_active' => true, 'order' => 1, 'smaregi_rank_id' => '1', 'point_earn_rate' => 100],
            ['key' => 'gold', 'label' => 'Gold', 'min_points' => 100, 'is_active' => true, 'order' => 2, 'smaregi_rank_id' => '2', 'point_earn_rate' => 50],
        ], (int) $project->id);

        return Member::factory()->create([
            'project_id' => $project->id, 'rank' => $rank, 'smaregi_customer_id' => 'customer-'.$project->id,
        ]);
    }

    private function api(): MockInterface
    {
        $api = \Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturnUsing(fn ($id) => 'contract-'.$id);
        $api->shouldReceive('clearLastErrorDetail')->andReturnNull();
        $api->shouldReceive('getLastErrorDetail')->andReturn('temporary failure');
        app()->instance(SmaregiApiService::class, $api);

        return $api;
    }

    public function test_stale_remote_completion_after_new_worker_success_keeps_reconciliation(): void
    {
        $member = $this->member('gold');
        $api = $this->api();
        $calls = 0;
        $api->shouldReceive('updateCustomer')->times(3)->andReturnUsing(function ($contract, $customer, $body) use ($member, &$calls): array {
            if (++$calls === 1) {
                $this->assertSame('2', $body['rank']);
                SmaregiMemberRankSync::whereKey($member->id)->update(['leased_until' => now()->subMinute()]);
                $member->update(['rank' => 'bronze']);
                app(SmaregiCustomerRankAssignmentService::class)->assignRankToCustomer($member, 'bronze');
                $this->assertDatabaseCount('smaregi_member_rank_syncs', 0);
                // The old request completes its external write after the newer one has succeeded.
            } else {
                $this->assertSame('1', $body['rank']);
            }

            return ['customerId' => $customer];
        });
        app(SmaregiCustomerRankAssignmentService::class)->assignRankToCustomer($member, 'gold');
        $this->assertDatabaseCount('smaregi_member_rank_syncs', 1);
        $this->artisan('smaregi:retry-member-ranks')->assertSuccessful();
        $this->assertDatabaseCount('smaregi_member_rank_syncs', 0);
    }

    public function test_failed_transition_recovers_without_another_rank_change(): void
    {
        $member = $this->member('bronze');
        $api = $this->api();
        $api->shouldReceive('updateCustomer')->twice()->andReturn(null, ['customerId' => $member->smaregi_customer_id]);
        $this->mock(MemberRankCalculator::class, function ($mock): void {
            $mock->shouldReceive('getRankInfo')->andReturn(['current_rank' => 'gold', 'current_rank_label' => 'Gold']);
        });
        $service = app(MemberRankSyncService::class);
        $this->assertTrue($service->sync($member));
        $this->assertSame('gold', $member->fresh()->rank);
        $this->assertDatabaseCount('smaregi_member_rank_syncs', 1);
        $this->assertFalse($service->sync($member));
        $this->travel(1)->minutes();

        $this->artisan('smaregi:retry-member-ranks')->assertSuccessful();

        $this->assertDatabaseCount('smaregi_member_rank_syncs', 0);
        $this->assertDatabaseCount('member_rank_histories', 1);
    }

    public function test_outer_rollback_discards_rank_and_sync_request_without_external_update(): void
    {
        $member = $this->member('bronze');
        $api = $this->api();
        $api->shouldNotReceive('updateCustomer');
        DB::beginTransaction();
        try {
            $member->update(['rank' => 'gold']);
            app(SmaregiCustomerRankAssignmentService::class)->assignRankToCustomer($member, 'gold');
            $this->assertDatabaseCount('smaregi_member_rank_syncs', 1);
        } finally {
            DB::rollBack();
        }
        $this->assertSame('bronze', $member->fresh()->rank);
        $this->assertDatabaseCount('smaregi_member_rank_syncs', 0);
    }

    public function test_old_worker_cannot_acknowledge_a_new_rank_request(): void
    {
        $member = $this->member();
        $api = $this->api();
        $calls = 0;
        $api->shouldReceive('updateCustomer')->twice()->andReturnUsing(function ($contract, $customer, $body) use ($member, &$calls): array {
            $calls++;
            if ($calls === 1) {
                $this->assertSame('2', $body['rank']);
                $member->update(['rank' => 'bronze']);
                app(SmaregiCustomerRankAssignmentService::class)->assignRankToCustomer($member, 'bronze');
            } else {
                $this->assertSame('1', $body['rank']);
            }

            return ['customerId' => $customer];
        });
        app(SmaregiCustomerRankAssignmentService::class)->assignRankToCustomer($member, 'gold');
        $this->assertDatabaseCount('smaregi_member_rank_syncs', 1);
        $this->assertNull(SmaregiMemberRankSync::findOrFail($member->id)->lease_token);

        $this->artisan('smaregi:retry-member-ranks')->assertSuccessful();

        $this->assertDatabaseCount('smaregi_member_rank_syncs', 0);
    }

    public function test_scanner_recovers_a_stopped_worker_after_lease_expiry(): void
    {
        $member = $this->member();
        SmaregiMemberRankSync::create([
            'member_id' => $member->id, 'project_id' => $member->project_id,
            'request_token' => 'request', 'retry_at' => now(), 'lease_token' => 'stopped-worker',
            'leased_until' => now()->addMinutes(5),
        ]);
        $api = $this->api();
        $api->shouldReceive('updateCustomer')->once()->andReturn(['customerId' => $member->smaregi_customer_id]);
        $this->artisan('smaregi:retry-member-ranks')->assertSuccessful();
        $this->assertDatabaseCount('smaregi_member_rank_syncs', 1);
        $this->travel(6)->minutes();

        $this->artisan('smaregi:retry-member-ranks')->assertSuccessful();

        $this->assertDatabaseCount('smaregi_member_rank_syncs', 0);
    }

    public function test_a_failed_member_does_not_block_later_project_and_is_reported(): void
    {
        $members = [$this->member(), $this->member()];
        foreach ($members as $member) {
            SmaregiMemberRankSync::create([
                'member_id' => $member->id, 'project_id' => $member->project_id,
                'request_token' => 'request-'.$member->id, 'retry_at' => now(),
            ]);
        }
        $api = $this->api();
        $api->shouldReceive('updateCustomer')->twice()->andReturnUsing(function ($contract, $customer) use ($members): array {
            if ($customer === $members[0]->smaregi_customer_id) {
                throw new \RuntimeException('provider unavailable');
            }
            $this->assertSame('contract-'.$members[1]->project_id, $contract);

            return ['customerId' => $customer];
        });

        $this->artisan('smaregi:retry-member-ranks')->assertExitCode(1);

        $this->assertDatabaseHas('smaregi_member_rank_syncs', ['member_id' => $members[0]->id]);
        $this->assertDatabaseMissing('smaregi_member_rank_syncs', ['member_id' => $members[1]->id]);
        $this->assertNull(SmaregiMemberRankSync::findOrFail($members[0]->id)->leased_until);
    }

    public function test_a_stopped_old_worker_cannot_acknowledge_a_new_lease(): void
    {
        $member = $this->member();
        $api = $this->api();
        $calls = 0;
        $api->shouldReceive('updateCustomer')->twice()->andReturnUsing(function () use ($member, &$calls): ?array {
            $calls++;
            if ($calls === 1) {
                $this->travel(6)->minutes();
                $this->assertFalse(app(SmaregiCustomerRankAssignmentService::class)->retryPending((int) $member->id));

                return ['customerId' => $member->smaregi_customer_id];
            }

            return null;
        });

        app(SmaregiCustomerRankAssignmentService::class)->assignRankToCustomer($member, 'gold');

        $request = SmaregiMemberRankSync::findOrFail($member->id);
        $this->assertTrue($request->retry_at->gt(now()));
        $this->assertNull($request->lease_token);
    }

    public function test_retry_uses_latest_rate_and_restores_another_project_context(): void
    {
        $member = $this->member();
        $other = Project::factory()->create();
        AdminContext::setCurrentProject($other);
        $api = $this->api();
        $calls = 0;
        $api->shouldReceive('updateCustomer')->twice()->andReturnUsing(function ($contract, $customer, $body) use ($member, &$calls): ?array {
            $this->assertSame((int) $member->project_id, AdminContext::getCurrentProjectId());
            $calls++;
            $this->assertSame($calls === 1 ? '50' : '80', $body['pointGivingUnitPrice']);

            return $calls === 1 ? null : ['customerId' => $customer];
        });
        app(SmaregiCustomerRankAssignmentService::class)->assignRankToCustomer($member, 'gold');
        $this->assertSame((int) $other->id, AdminContext::getCurrentProjectId());
        app(MemberRankSettingStore::class)->setMemberRankSettings([
            ['key' => 'gold', 'label' => 'Gold', 'min_points' => 100, 'is_active' => true, 'order' => 1, 'smaregi_rank_id' => '2', 'point_earn_rate' => 80],
        ], (int) $member->project_id);
        $this->travel(1)->minutes();

        $this->artisan('smaregi:retry-member-ranks')->assertSuccessful();

        $this->assertSame((int) $other->id, AdminContext::getCurrentProjectId());
        $this->assertDatabaseCount('smaregi_member_rank_syncs', 0);
    }

    public function test_missing_contract_preserves_work_and_defers_external_attempts(): void
    {
        $member = $this->member();
        $this->mock(SmaregiApiService::class, function ($mock): void {
            $mock->shouldReceive('getContractIdForProject')->once()->andReturnNull();
            $mock->shouldNotReceive('updateCustomer');
        });

        app(SmaregiCustomerRankAssignmentService::class)->assignRankToCustomer($member, 'gold');

        $request = SmaregiMemberRankSync::findOrFail($member->id);
        $this->assertSame(now()->addHour()->toDateTimeString(), $request->retry_at->toDateTimeString());
        $this->assertNull($request->lease_token);
        $this->artisan('smaregi:retry-member-ranks')->assertSuccessful();
    }

    public function test_admin_rank_change_uses_current_rank_instead_of_a_stale_model(): void
    {
        $member = $this->member();
        Member::withoutGlobalScopes()->whereKey($member->id)->update(['rank' => 'bronze']);
        AdminContext::setCurrentProject(Project::findOrFail($member->project_id));
        config(['smaregi.client_id' => '', 'smaregi.client_secret' => '']);
        $api = $this->api();
        $api->shouldReceive('updateCustomer')->once()->andReturnNull();

        app(MemberAdminService::class)->updateMemberProfile($member, [], ['rank' => 'gold']);

        $this->assertSame('gold', $member->fresh()->rank);
        $this->assertDatabaseHas('member_rank_histories', [
            'member_id' => $member->id, 'from_rank' => 'bronze', 'to_rank' => 'gold',
        ]);
        $this->assertDatabaseHas('smaregi_member_rank_syncs', ['member_id' => $member->id]);
    }

    public function test_unlinked_and_stale_rank_requests_do_not_create_sync_work(): void
    {
        $member = $this->member();
        $api = $this->api();
        $api->shouldNotReceive('updateCustomer');
        $service = app(SmaregiCustomerRankAssignmentService::class);
        $service->assignRankToCustomer($member, 'bronze');
        $member->update(['smaregi_customer_id' => null]);
        $service->assignRankToCustomer($member, 'gold');

        $this->assertDatabaseCount('smaregi_member_rank_syncs', 0);
    }
}
