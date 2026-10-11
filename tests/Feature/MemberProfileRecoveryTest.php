<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Member;
use App\Models\Project;
use App\Models\SmaregiMemberProfileSync;
use App\Models\User;
use App\Services\MemberService;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCustomerPointsInboundSyncService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MemberProfileRecoveryTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    private function user(): User
    {
        config(['smaregi.inbound_sync_on_member_fetch' => false]);
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        ShopSettingStore::setValue('smaregi_contract_id', 'contract', 'string', (int) $project->id);
        $user = User::factory()->create(['project_id' => $project->id, 'name' => 'Original']);
        Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id,
            'smaregi_customer_id' => 'external-member', 'points' => 0]);

        return $user->fresh(['member']);
    }

    public function test_next_fetch_preserves_local_profile_after_failed_patch(): void
    {
        $user = $this->user();
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('clearLastErrorDetail')->once();
        $api->shouldReceive('updateCustomer')->once()->andReturnNull();
        $api->shouldReceive('getLastErrorDetail')->andReturn('offline');
        $api->shouldReceive('getCustomer')->andReturn(['lastName' => 'Original', 'firstName' => '']);
        $api->shouldReceive('getCustomerPoint')->andReturn(['point' => 0]);
        app(MemberService::class)->updateProfile($user, ['name' => 'Changed']);
        config(['smaregi.inbound_sync_on_member_fetch' => true]);

        $response = app(MemberService::class)->getMemberData($user->fresh(['member']));

        $this->assertSame('Changed', $response['profile']['name']);
        $this->assertSame('Changed', $user->fresh()->name);
        $this->assertNull(SmaregiMemberProfileSync::findOrFail($user->member->id)->delivered_at);
    }

    public function test_scanner_recovers_failed_patch_without_queue_dispatch(): void
    {
        $user = $this->user();
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('clearLastErrorDetail')->twice();
        $api->shouldReceive('updateCustomer')->once()->andReturnNull();
        $api->shouldReceive('updateCustomer')->once()->with('contract', 'external-member', [
            'lastName' => 'Changed', 'firstName' => '',
        ])->andReturnUsing(function (): array {
            $this->assertSame(0, DB::transactionLevel());

            return [];
        });
        app(MemberService::class)->updateProfile($user, ['name' => 'Changed']);
        $row = SmaregiMemberProfileSync::findOrFail($user->member->id);
        $this->assertNull($row->delivered_at);
        $this->assertNull($row->leased_until);
        $this->travel(2)->minutes();

        $this->artisan('smaregi:retry-member-profiles')->assertSuccessful();

        $this->assertNotNull($row->fresh()->delivered_at);
        $this->artisan('smaregi:retry-member-profiles')->assertSuccessful();
    }

    public function test_rollback_removes_profile_request_with_local_change(): void
    {
        $user = $this->user();
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldNotReceive('updateCustomer');
        DB::beginTransaction();
        try {
            app(MemberService::class)->updateProfile($user, ['name' => 'Changed']);
            $this->assertSame('Changed', SmaregiMemberProfileSync::findOrFail($user->member->id)->body['lastName']);
        } finally {
            DB::rollBack();
        }
        $this->assertNull(SmaregiMemberProfileSync::find($user->member->id));
        $this->assertSame('Original', $user->fresh()->name);
    }

    public function test_new_partial_edit_keeps_pending_fields_and_current_values(): void
    {
        $user = $this->user();
        ShopSettingStore::setProfileFieldSettings(['smaregi_fields' => [], 'custom_fields' => [
            ['key' => 'choice', 'label' => 'Choice', 'input_type' => 'text', 'smaregi_no' => 1],
            ['key' => 'other', 'label' => 'Other', 'input_type' => 'text', 'smaregi_no' => 2],
        ]], (int) $user->project_id);
        $user->member->update(['profile_extra' => ['other' => 'Keep']]);
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('clearLastErrorDetail')->twice();
        $api->shouldReceive('updateCustomer')->once()->with('contract', 'external-member', [
            'lastName' => 'Changed', 'firstName' => '', 'customFields' => [['no' => 1, 'value' => 'New']],
        ])->andReturnNull();
        $api->shouldReceive('updateCustomer')->once()->with('contract', 'external-member', [
            'lastName' => 'Changed', 'firstName' => '', 'phoneNumber' => '09012345678',
            'customFields' => [['no' => 1, 'value' => 'New']],
        ])->andReturn([]);
        app(MemberService::class)->updateProfile($user->fresh(['member']), ['name' => 'Changed', 'choice' => 'New']);
        app(MemberService::class)->updateProfile($user->fresh(['member']), ['phone' => '09012345678']);
        $this->assertNotNull(SmaregiMemberProfileSync::findOrFail($user->member->id)->delivered_at);
        $this->assertSame('Keep', $user->member->fresh()->profile_extra['other']);
    }

    public function test_inbound_get_before_new_request_ack_cannot_restore_old_profile(): void
    {
        $user = $this->user();
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('clearLastErrorDetail')->once();
        $api->shouldReceive('updateCustomer')->once()->andReturn([]);
        $api->shouldReceive('getCustomer')->once()->andReturnUsing(function () use ($user): array {
            app(MemberService::class)->updateProfile($user, ['name' => 'Changed']);
            $this->assertNotNull(SmaregiMemberProfileSync::findOrFail($user->member->id)->delivered_at);

            return ['lastName' => 'Original', 'firstName' => ''];
        });
        $api->shouldReceive('getCustomerPoint')->andReturn(['point' => 0]);

        app(SmaregiCustomerPointsInboundSyncService::class)->syncMemberFromSmaregi($user->member);

        $this->assertSame('Changed', $user->fresh()->name);
    }

    public function test_acked_history_does_not_overwrite_legitimate_inbound_edits_on_next_patch(): void
    {
        $user = $this->user();
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('clearLastErrorDetail')->twice();
        $api->shouldReceive('updateCustomer')->once()->with('contract', 'external-member', [
            'lastName' => 'Changed', 'firstName' => '',
        ])->andReturn([]);
        $api->shouldReceive('updateCustomer')->once()->with('contract', 'external-member', [
            'phoneNumber' => '09012345678',
        ])->andReturn([]);
        $api->shouldReceive('getCustomer')->andReturn(['lastName' => 'Remote', 'firstName' => '']);
        $api->shouldReceive('getCustomerPoint')->andReturn(['point' => 0]);
        app(MemberService::class)->updateProfile($user, ['name' => 'Changed']);
        app(SmaregiCustomerPointsInboundSyncService::class)->syncMemberFromSmaregi($user->member);
        $this->assertSame('Remote', $user->fresh()->name);

        app(MemberService::class)->updateProfile($user->fresh(['member']), ['phone' => '09012345678']);

        $this->assertSame('Remote', $user->fresh()->name);
    }

    public function test_late_worker_cannot_ack_newer_profile_and_leaves_latest_repair(): void
    {
        $user = $this->user();
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('clearLastErrorDetail')->twice();
        $api->shouldReceive('updateCustomer')->once()->with('contract', 'external-member', [
            'lastName' => 'Old', 'firstName' => '',
        ])->andReturnUsing(function () use ($user): array {
            app(MemberService::class)->updateProfile($user->fresh(['member']), ['name' => 'Latest']);
            $row = SmaregiMemberProfileSync::findOrFail($user->member->id);
            $this->assertSame('Latest', $row->body['lastName']);
            $this->assertNull($row->delivered_at);
            $this->assertNotNull($row->leased_until);

            return [];
        });
        $api->shouldReceive('updateCustomer')->once()->with('contract', 'external-member', [
            'lastName' => 'Latest', 'firstName' => '',
        ])->andReturn([]);
        app(MemberService::class)->updateProfile($user, ['name' => 'Old']);
        $row = SmaregiMemberProfileSync::findOrFail($user->member->id);
        $this->assertNull($row->delivered_at);
        $this->assertNull($row->leased_until);
        $this->assertSame('Latest', $row->body['lastName']);

        $this->artisan('smaregi:retry-member-profiles')->assertSuccessful();

        $this->assertNotNull($row->fresh()->delivered_at);
    }

    public function test_pending_profile_does_not_block_independent_point_import(): void
    {
        $user = $this->user();
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('clearLastErrorDetail')->once();
        $api->shouldReceive('updateCustomer')->once()->andReturnNull();
        $api->shouldReceive('getCustomer')->andReturn(['lastName' => 'Original', 'firstName' => '']);
        $api->shouldReceive('getCustomerPoint')->andReturn(['point' => 55]);
        app(MemberService::class)->updateProfile($user, ['name' => 'Changed']);

        app(SmaregiCustomerPointsInboundSyncService::class)->syncMemberFromSmaregi($user->member);

        $this->assertSame('Changed', $user->fresh()->name);
        $this->assertSame(55, $user->member->fresh()->getTotalPointsFromTransactions());
    }

    public function test_clearing_a_pending_email_never_replays_the_superseded_value(): void
    {
        $user = $this->user();
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('clearLastErrorDetail')->once();
        $api->shouldReceive('updateCustomer')->once()->with('contract', 'external-member', [
            'mailAddress' => 'old@example.com',
        ])->andReturnNull();
        app(MemberService::class)->updateProfile($user, ['email' => 'old@example.com']);
        $token = SmaregiMemberProfileSync::findOrFail($user->member->id)->request_token;
        app(MemberService::class)->updateProfile($user->fresh(['member']), ['email' => null]);
        $row = SmaregiMemberProfileSync::findOrFail($user->member->id);
        $this->assertNotSame($token, $row->request_token);
        $this->assertSame([], $row->body);
        $this->travel(2)->minutes();
        $this->artisan('smaregi:retry-member-profiles')->assertSuccessful();
        $this->assertNull($user->fresh()->email);
    }

    public function test_structured_address_edit_uses_current_parts_instead_of_old_formatted_address(): void
    {
        $user = $this->user();
        $user->member->update(['address' => 'Old formatted', 'prefecture' => 'Tokyo', 'city' => 'Old', 'street' => '1']);
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('clearLastErrorDetail')->once();
        $api->shouldReceive('updateCustomer')->once()->with('contract', 'external-member', [
            'address' => 'TokyoNew1',
        ])->andReturn([]);
        app(MemberService::class)->updateProfile($user->fresh(['member']), ['city' => 'New']);
    }

    public function test_contract_change_does_not_redirect_a_pending_request(): void
    {
        $user = $this->user();
        $contract = 'contract';
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturnUsing(function () use (&$contract): string {
            return $contract;
        });
        $api->shouldReceive('clearLastErrorDetail')->once();
        $api->shouldReceive('updateCustomer')->once()->with('contract', 'external-member', [
            'lastName' => 'Changed', 'firstName' => '',
        ])->andReturnNull();
        app(MemberService::class)->updateProfile($user, ['name' => 'Changed']);
        $contract = 'different-contract';
        $this->travel(2)->minutes();
        $this->artisan('smaregi:retry-member-profiles')->assertSuccessful();
    }

    public function test_expired_old_worker_reopens_repair_after_newer_worker_ack(): void
    {
        $user = $this->user();
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('clearLastErrorDetail')->times(3);
        $api->shouldReceive('updateCustomer')->once()->with('contract', 'external-member', [
            'lastName' => 'Old', 'firstName' => '',
        ])->andReturnUsing(function () use ($user): array {
            SmaregiMemberProfileSync::whereKey($user->member->id)->update(['leased_until' => now()->subMinute()]);
            app(MemberService::class)->updateProfile($user->fresh(['member']), ['name' => 'Latest']);
            $this->assertNotNull(SmaregiMemberProfileSync::findOrFail($user->member->id)->delivered_at);

            return [];
        });
        $api->shouldReceive('updateCustomer')->twice()->with('contract', 'external-member', [
            'lastName' => 'Latest', 'firstName' => '',
        ])->andReturn([]);
        app(MemberService::class)->updateProfile($user, ['name' => 'Old']);
        $row = SmaregiMemberProfileSync::findOrFail($user->member->id);
        $this->assertNull($row->delivered_at);
        $this->assertSame('Latest', $row->body['lastName']);
        $this->artisan('smaregi:retry-member-profiles')->assertSuccessful();
        $this->assertNotNull($row->fresh()->delivered_at);
    }

    public function test_unrelated_edit_keeps_pending_explicit_address(): void
    {
        $user = $this->user();
        $user->member->update(['prefecture' => 'Tokyo', 'city' => 'Old', 'street' => '1']);
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract');
        $api->shouldReceive('clearLastErrorDetail')->twice();
        $api->shouldReceive('updateCustomer')->once()->with('contract', 'external-member', [
            'address' => 'New formatted',
        ])->andReturnNull();
        $api->shouldReceive('updateCustomer')->once()->with('contract', 'external-member', [
            'phoneNumber' => '09012345678', 'address' => 'New formatted',
        ])->andReturn([]);
        app(MemberService::class)->updateProfile($user, ['address' => 'New formatted']);
        app(MemberService::class)->updateProfile($user->fresh(['member']), ['phone' => '09012345678']);
    }

    public function test_late_worker_does_not_reopen_request_for_a_changed_contract(): void
    {
        $user = $this->user();
        $contract = 'contract';
        $api = $this->mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturnUsing(function () use (&$contract): string {
            return $contract;
        });
        $api->shouldReceive('clearLastErrorDetail')->once();
        $api->shouldReceive('updateCustomer')->once()->andReturnUsing(function () use ($user, &$contract): array {
            $contract = 'different-contract';
            SmaregiMemberProfileSync::whereKey($user->member->id)->update([
                'request_token' => 'superseded', 'delivered_at' => now(),
            ]);

            return [];
        });
        app(MemberService::class)->updateProfile($user, ['name' => 'Changed']);
        $row = SmaregiMemberProfileSync::findOrFail($user->member->id);
        $this->assertSame('contract', $row->contract_id);
        $this->assertNotNull($row->delivered_at);
        $this->artisan('smaregi:retry-member-profiles')->assertSuccessful();
    }
}
