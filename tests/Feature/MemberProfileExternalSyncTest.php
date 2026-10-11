<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Models\Store;
use App\Models\User;
use App\Services\MemberService;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerPointsInboundSyncService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MemberProfileExternalSyncTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    private function memberUser(): User
    {
        config(['smaregi.inbound_sync_on_member_fetch' => false]);
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id, 'name' => 'Original']);
        Member::factory()->create([
            'project_id' => $project->id, 'user_id' => $user->id, 'smaregi_customer_id' => 'external-member',
        ]);

        return $user->fresh(['member']);
    }

    public function test_outer_rollback_never_sends_profile_to_smaregi(): void
    {
        $user = $this->memberUser();
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('updateCustomer')->never();
        DB::beginTransaction();
        try {
            app(MemberService::class)->updateProfile($user, ['name' => 'Changed']);
            $this->assertSame('Changed', $user->fresh()->name);
        } finally {
            DB::rollBack();
        }
        $this->assertSame('Original', $user->fresh()->name);
    }

    public function test_profile_sends_only_after_outer_commit(): void
    {
        $user = $this->memberUser();
        $sent = false;
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('clearLastErrorDetail')->once();
        $api->shouldReceive('updateCustomer')->once()->with('contract', 'external-member', [
            'lastName' => 'Changed', 'firstName' => '',
        ])->andReturnUsing(function () use (&$sent): array {
            $this->assertSame(0, DB::transactionLevel());
            $sent = true;

            return [];
        });
        DB::transaction(function () use ($user, &$sent): void {
            app(MemberService::class)->updateProfile($user, ['name' => 'Changed']);
            $this->assertFalse($sent);
        });
        $this->assertTrue($sent);
    }

    public function test_external_exception_preserves_successful_local_response(): void
    {
        $user = $this->memberUser();
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('clearLastErrorDetail')->once();
        $api->shouldReceive('updateCustomer')->once()->andThrow(new \RuntimeException('offline'));

        $result = app(MemberService::class)->updateProfile($user, ['name' => 'Changed']);

        $this->assertSame('Changed', $result['profile']['name']);
        $this->assertSame('Changed', $user->fresh()->name);
    }

    public function test_deferred_sync_uses_saved_project_and_restores_caller_context(): void
    {
        $user = $this->memberUser();
        $store = Store::where('project_id', $user->project_id)->firstOrFail();
        $store->update(['smaregi_store_id' => 'external-store']);
        $other = Project::factory()->create();
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->once()->with((int) $user->project_id)
            ->andReturnUsing(function () use ($user): string {
                $this->assertSame((int) $user->project_id, AdminContext::getCurrentProject()->id);

                return 'contract';
            });
        $api->shouldReceive('clearLastErrorDetail')->once();
        $api->shouldReceive('updateCustomer')->once()
            ->with('contract', 'external-member', ['storeId' => 'external-store'])->andReturn([]);

        DB::transaction(function () use ($user, $store, $other): void {
            app(MemberService::class)->updateProfile($user, ['customer_store_id' => (string) $store->id]);
            AdminContext::setCurrentProject($other);
        });

        $this->assertSame($other->id, AdminContext::getCurrentProject()->id);
    }

    public function test_update_response_does_not_import_the_old_remote_profile(): void
    {
        $user = $this->memberUser();
        config(['smaregi.inbound_sync_on_member_fetch' => true]);
        $inbound = $this->mock(SmaregiCustomerPointsInboundSyncService::class);
        $inbound->shouldReceive('syncMemberFromSmaregi')->never();
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('clearLastErrorDetail')->once();
        $api->shouldReceive('updateCustomer')->once()->andReturn([]);

        DB::transaction(function () use ($user): void {
            $result = app(MemberService::class)->updateProfile($user, ['name' => 'Changed']);
            $this->assertSame('Changed', $result['profile']['name']);
        });
    }
}
