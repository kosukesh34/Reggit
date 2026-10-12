<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Models\WalletPassRegistration;
use App\Services\AppleWalletPasskitService;
use App\Services\WalletPassSyncService;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WalletPassUpdateSnapshotTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        DB::purge('wallet_writer');
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_an_old_snapshot_does_not_advance_past_an_unseen_committed_update(): void
    {
        config(['wallet_pass.type_id' => 'pass.example.member']);
        $this->freezeTime();
        $project = Project::factory()->create();
        $member = Member::factory()->create(['project_id' => $project->id]);
        WalletPassRegistration::create([
            'project_id' => $project->id, 'member_id' => $member->id,
            'pass_type_identifier' => 'pass.example.member', 'serial_number' => 'member-'.$member->id,
            'device_library_identifier' => 'device', 'push_token' => 'push',
        ]);
        $sync = app(WalletPassSyncService::class);
        $sync->markMemberUpdated($member);
        $passes = app(AppleWalletPasskitService::class);
        $connection = DB::getDefaultConnection();
        config(['database.connections.wallet_writer' => config('database.connections.'.$connection)]);
        DB::beginTransaction();
        try {
            $initial = $passes->listUpdatedSerials('device', 'pass.example.member', null);
            DB::setDefaultConnection('wallet_writer');
            try {
                $sync->markMemberUpdated($member);
            } finally {
                DB::setDefaultConnection($connection);
            }
            $stale = $passes->listUpdatedSerials('device', 'pass.example.member', $initial['lastUpdated']);
            $this->assertSame([], $stale['serialNumbers']);
            $this->assertSame($initial['lastUpdated'], $stale['lastUpdated']);
        } finally {
            DB::rollBack();
        }
        $latest = $passes->listUpdatedSerials('device', 'pass.example.member', $stale['lastUpdated']);
        $this->assertSame(['member-'.$member->id], $latest['serialNumbers']);
    }
}
