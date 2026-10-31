<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Models\User;
use App\Services\AppleWalletPushService;
use App\Services\GoogleWalletPassService;
use App\Services\Member\MemberPointLedger;
use App\Services\MemberLiffWalletNotifier;
use App\Services\MemberWalletSummaryService;
use App\Services\MemberWalletSyncService;
use App\Services\WalletPassSyncService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MemberWalletSynchronizationConsistencyTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_wallet_notification_cannot_overwrite_a_concurrent_point_balance_update(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL and independent database connections.');
        }

        $member = $this->memberWithCachedPoints(70);
        $this->insertLedger($member, 'earned', 100, now()->addYear());
        $this->mockNotifications();
        $default = DB::getDefaultConnection();
        config(['database.connections.wallet_writer' => config('database.connections.'.$default)]);
        $writer = DB::connection('wallet_writer');
        $interleaved = false;
        $active = true;
        DB::listen(function ($query) use ($member, $writer, &$interleaved, &$active): void {
            if (! $active || $interleaved || ! str_contains($query->sql, 'from `point_transactions`')) {
                return;
            }
            $interleaved = true;
            // Insert after the notification's ledger SELECT has returned its old result.
            $writer->transaction(function () use ($member, $writer): void {
                $writer->table('point_transactions')->insert([
                    'project_id' => $member->project_id, 'member_id' => $member->id,
                    'type' => 'earned', 'points' => 50, 'description' => 'Concurrent award',
                    'expires_at' => now()->addYear(), 'created_at' => now(), 'updated_at' => now(),
                ]);
                $writer->table('members')->where('id', $member->id)->update(['points' => 150]);
            });
        });

        try {
            app(MemberWalletSyncService::class)->syncAfterMemberChanged($member);
            $active = false;
            $this->assertTrue($interleaved, 'The concurrent award must happen during notification processing.');
            $this->assertSame(150, (int) $member->fresh()->points);
            $this->assertSame(150, app(MemberPointLedger::class)->displayPoints($member->fresh()));
        } finally {
            $active = false;
            DB::purge('wallet_writer');
        }
    }

    public function test_wallet_notification_does_not_repair_a_stale_cached_balance(): void
    {
        $member = $this->memberWithCachedPoints(70);
        $this->insertLedger($member, 'earned', 100, now()->addYear());
        $this->mockNotifications();

        app(MemberWalletSyncService::class)->syncAfterMemberChanged($member);

        $this->assertSame(70, (int) $member->fresh()->points);
        $this->assertSame(100, app(MemberPointLedger::class)->displayPoints($member->fresh()));
    }

    public function test_wallet_summary_uses_unexpired_unspent_points_without_updating_the_cache(): void
    {
        $member = $this->memberWithCachedPoints(160);
        $this->insertLedger($member, 'earned', 60, now()->subDay());
        $this->insertLedger($member, 'earned', 100, now()->addYear());
        $this->insertLedger($member, 'used', -80);

        $summary = app(MemberWalletSummaryService::class)->summarize($member->user);

        $this->assertSame(20, $summary['points']);
        $this->assertSame(160, (int) $member->fresh()->points);
    }

    public function test_previous_owner_cannot_read_wallet_summary_from_cached_member(): void
    {
        $member = $this->memberWithCachedPoints(160);
        $this->insertLedger($member, 'earned', 100, now()->addYear());
        $user = $member->user->load('member');
        $newOwner = User::factory()->create(['project_id' => $member->project_id]);
        $member->update(['user_id' => $newOwner->id]);

        try {
            app(MemberWalletSummaryService::class)->summarize($user);
            $this->fail('Expected the previous owner to be rejected');
        } catch (\RuntimeException $exception) {
            $this->assertSame(401, $exception->getCode());
        }

        $this->assertSame(160, (int) $member->fresh()->points);
    }

    public function test_deleted_cached_member_cannot_read_wallet_summary(): void
    {
        $member = $this->memberWithCachedPoints(160);
        $this->insertLedger($member, 'earned', 100, now()->addYear());
        $user = $member->user->load('member');
        $member->delete();

        try {
            app(MemberWalletSummaryService::class)->summarize($user);
            $this->fail('Expected the deleted member to be rejected');
        } catch (\RuntimeException $exception) {
            $this->assertSame(401, $exception->getCode());
        }

        $this->assertSame(160, (int) Member::withTrashed()->findOrFail($member->id)->points);
    }

    public function test_apple_push_failure_does_not_block_google_wallet_sync(): void
    {
        $member = $this->memberWithCachedPoints(70);
        $this->mockNotifications();
        config([
            'wallet_pass.type_id' => 'pass.example.member',
            'wallet_pass.web_service_url' => 'https://wallet.example.test',
        ]);
        $this->mock(WalletPassSyncService::class, function ($mock): void {
            $mock->shouldReceive('markMemberUpdated')->once();
        });
        $this->mock(AppleWalletPushService::class, function ($mock): void {
            $mock->shouldReceive('notifyMemberUpdated')->once()
                ->andThrow(new \RuntimeException('Apple connection failed'));
        });

        app(MemberWalletSyncService::class)->syncAfterMemberChanged($member);
    }

    private function mockNotifications(): void
    {
        config(['wallet_pass.type_id' => '']);
        $google = \Mockery::mock(GoogleWalletPassService::class);
        $google->shouldReceive('canSyncExistingObjects')->once()->andReturn(true);
        $google->shouldReceive('syncLoyaltyObjectForMember')->once()->andReturn(true);
        $this->app->instance(GoogleWalletPassService::class, $google);
        $liff = \Mockery::mock(MemberLiffWalletNotifier::class);
        $liff->shouldReceive('notify')->once()->andReturnNull();
        $this->app->instance(MemberLiffWalletNotifier::class, $liff);
    }

    private function memberWithCachedPoints(int $points): Member
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);

        return Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'points' => $points]);
    }

    private function insertLedger(Member $member, string $type, int $points, ?\DateTimeInterface $expiresAt = null): void
    {
        DB::table('point_transactions')->insert([
            'project_id' => $member->project_id, 'member_id' => $member->id,
            'type' => $type, 'points' => $points, 'description' => 'Wallet ledger fixture',
            'expires_at' => $expiresAt, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
