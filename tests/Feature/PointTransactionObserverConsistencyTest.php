<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\User;
use App\Services\MemberWalletSyncService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PointTransactionObserverConsistencyTest extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $wallets = \Mockery::mock(MemberWalletSyncService::class);
        $wallets->shouldReceive('syncAfterMemberChanged')->andReturnNull();
        $this->app->instance(MemberWalletSyncService::class, $wallets);
    }

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_created_updated_and_deleted_observe_the_final_committed_ledger(): void
    {
        $member = $this->memberWithLedger();
        DB::beginTransaction();
        $entry = $this->earned($member, 50);
        $this->assertSame(100, (int) $member->fresh()->lifetime_granted_points);
        DB::commit();
        $this->assertSame(150, (int) $member->fresh()->lifetime_granted_points);

        DB::beginTransaction();
        $entry->update(['points' => 70]);
        $this->assertSame(150, (int) $member->fresh()->lifetime_granted_points);
        DB::commit();
        $this->assertSame(170, (int) $member->fresh()->lifetime_granted_points);

        DB::beginTransaction();
        $entry->delete();
        $this->assertSame(170, (int) $member->fresh()->lifetime_granted_points);
        DB::commit();
        $this->assertSame(100, (int) $member->fresh()->lifetime_granted_points);
    }

    public function test_reversal_before_earning_does_not_make_lifetime_total_depend_on_callback_order(): void
    {
        $member = $this->memberWithLedger(false);
        DB::transaction(function () use ($member): void {
            PointTransaction::create([
                'project_id' => $member->project_id, 'member_id' => $member->id,
                'type' => 'reversed', 'points' => -50, 'description' => 'Earlier reversal',
            ]);
            $this->earned($member, 100);
        });

        $this->assertSame(50, (int) $member->fresh()->lifetime_granted_points);
    }

    public function test_concurrent_committed_earnings_are_both_included_in_lifetime_total(): void
    {
        $this->assertConcurrentLifetimeTotal(false);
    }

    public function test_recalculation_cannot_overwrite_an_earning_committed_after_its_ledger_read(): void
    {
        $this->assertConcurrentLifetimeTotal(true);
    }

    private function assertConcurrentLifetimeTotal(bool $recalculateFirst): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires MySQL and pcntl for independent concurrent connections.');
        }

        $member = $this->memberWithLedger();
        if ($recalculateFirst) {
            $member->update(['lifetime_granted_points' => 50]);
        }
        $directory = sys_get_temp_dir().'/point-observer-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        DB::disconnect();
        $workers = [];
        try {
            foreach ([50, 70] as $index => $points) {
                $pid = pcntl_fork();
                $this->assertNotSame(-1, $pid);
                if ($pid === 0) {
                    try {
                        DB::purge();
                        DB::statement('SET SESSION innodb_lock_wait_timeout = 5');
                        $firstRead = true;
                        DB::listen(function ($query) use ($directory, $index, $recalculateFirst, &$firstRead): void {
                            $table = $recalculateFirst && $index === 0 ? 'point_transactions' : 'members';
                            if (! $firstRead || ! str_contains($query->sql, 'from `'.$table.'`')) {
                                return;
                            }
                            $firstRead = false;
                            file_put_contents($directory.'/read-'.$index, 'ready');
                            if ($index === 0) {
                                $this->waitUntil(fn () => is_file($directory.'/release'));
                            }
                        });
                        file_put_contents($directory.'/connection-'.$index, (string) DB::selectOne('SELECT CONNECTION_ID() AS id')->id);
                        if ($recalculateFirst && $index === 0) {
                            $member->recalcLifetimeGrantedPoints();
                        } else {
                            DB::transaction(fn () => $this->earned($member, $points));
                        }
                        file_put_contents($directory.'/result-'.$index, 'ok');
                        exit(0);
                    } catch (\Throwable $e) {
                        file_put_contents($directory.'/result-'.$index, get_class($e).': '.$e->getMessage());
                        exit(1);
                    }
                }
                $workers[] = $pid;
                if ($index === 0) {
                    $this->waitUntil(fn () => is_file($directory.'/read-0'));
                }
            }
            $this->waitUntil(fn () => is_file($directory.'/connection-1'));
            $connectionId = (int) file_get_contents($directory.'/connection-1');
            // For recalculation, let an unlocked writer finish before releasing the stale result.
            $this->waitUntil(function () use ($directory, $connectionId, $recalculateFirst): bool {
                $marker = $recalculateFirst ? '/result-1' : '/read-1';
                if (is_file($directory.$marker)) {
                    return true;
                }

                return DB::selectOne(
                    'SELECT COUNT(*) AS waiting FROM information_schema.innodb_trx WHERE trx_mysql_thread_id = ? AND trx_state = ?',
                    [$connectionId, 'LOCK WAIT'],
                )->waiting > 0;
            });
            file_put_contents($directory.'/release', 'release');
            foreach ($workers as $pid) {
                $status = 0;
                $this->waitUntil(fn () => pcntl_waitpid($pid, $status, WNOHANG) === $pid);
            }
            $workers = [];
            foreach ([0, 1] as $index) {
                $this->assertSame('ok', file_get_contents($directory.'/result-'.$index));
            }
            $this->assertSame($recalculateFirst ? 170 : 220, (int) $member->fresh()->lifetime_granted_points);
        } finally {
            file_put_contents($directory.'/release', 'release');
            foreach ($workers as $pid) {
                posix_kill($pid, SIGKILL);
                pcntl_waitpid($pid, $status);
            }
            foreach (glob($directory.'/*') as $file) {
                unlink($file);
            }
            rmdir($directory);
            DB::purge();
            $this->truncateDatabaseTables();
        }
    }

    private function memberWithLedger(bool $withInitialEarning = true): Member
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create([
            'project_id' => $project->id, 'user_id' => $user->id,
            'points' => $withInitialEarning ? 100 : 0,
            'lifetime_granted_points' => $withInitialEarning ? 100 : 0,
        ]);
        if ($withInitialEarning) {
            PointTransaction::withoutEvents(fn () => $this->earned($member, 100));
        }

        return $member;
    }

    private function earned(Member $member, int $points): PointTransaction
    {
        return PointTransaction::create([
            'project_id' => $member->project_id, 'member_id' => $member->id,
            'type' => 'earned', 'points' => $points, 'description' => 'Test earning',
            'expires_at' => now()->addYear(),
        ]);
    }

    private function waitUntil(callable $ready): void
    {
        $deadline = microtime(true) + 5;
        while (! $ready()) {
            if (microtime(true) >= $deadline) {
                throw new \RuntimeException('Timed out waiting for point observer worker.');
            }
            usleep(10000);
        }
    }
}
