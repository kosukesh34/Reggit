<?php

namespace Tests\Feature;

use App\Domain\Member\Repositories\MemberRepository;
use App\Domain\Order\Exceptions\OrderOperationException;
use App\Models\Member;
use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\Project;
use App\Models\User;
use App\Services\Member\MemberPointLedger;
use App\Services\OrderRewardService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MemberPointLedgerConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_two_connections_backfill_legacy_points_only_once(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires MySQL and pcntl for independent concurrent connections.');
        }

        $member = $this->legacyMember();
        $directory = sys_get_temp_dir().'/member-ledger-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        DB::disconnect();
        $workers = [];

        try {
            foreach ([0, 1] as $index) {
                $pid = pcntl_fork();
                $this->assertNotSame(-1, $pid);
                if ($pid === 0) {
                    try {
                        DB::purge();
                        DB::statement('SET SESSION innodb_lock_wait_timeout = 5');
                        $firstRead = true;
                        DB::listen(function ($query) use ($directory, $index, &$firstRead): void {
                            if (! $firstRead || ! str_contains($query->sql, 'from `point_transactions`')) {
                                return;
                            }
                            $firstRead = false;
                            file_put_contents($directory.'/read-'.$index, 'ready');
                            if ($index === 0) {
                                $this->waitUntil(fn () => is_file($directory.'/release'));
                            }
                        });
                        file_put_contents($directory.'/connection-'.$index, (string) DB::selectOne('SELECT CONNECTION_ID() AS id')->id);
                        app(MemberPointLedger::class)->sync($member);
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
            // Release only after the second worker has read, or is blocked by the first worker's row lock.
            $this->waitUntil(function () use ($directory, $connectionId): bool {
                if (is_file($directory.'/read-1')) {
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
            $this->assertSingleBackfill($member);
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
            RefreshDatabaseState::$migrated = false;
        }
    }

    public function test_existing_backfill_is_seen_after_an_outer_repeatable_read_snapshot(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL repeatable-read snapshots.');
        }

        $member = $this->legacyMember();
        $default = DB::getDefaultConnection();
        config(['database.connections.ledger_writer' => config('database.connections.'.$default)]);
        $writer = DB::connection('ledger_writer');
        DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        DB::beginTransaction();
        try {
            $this->assertSame(0, PointTransaction::where('member_id', $member->id)->count());
            $writer->table('point_transactions')->insert([
                'project_id' => $member->project_id,
                'member_id' => $member->id,
                'type' => 'earned',
                'points' => 100,
                'description' => MemberPointLedger::POINTS_LEDGER_BACKFILL_DESCRIPTION,
                'expires_at' => now()->addYear(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->assertSame(0, PointTransaction::where('member_id', $member->id)->count(), 'The outer transaction still has its old snapshot.');
            app(MemberPointLedger::class)->sync($member);
            $repository = app(MemberRepository::class);
            $aggregate = $repository->findByIdForUpdate((int) $member->id, (int) $member->project_id);
            $this->assertNotNull($aggregate);
            $this->assertSame(100, $aggregate->displayPoints());
            $aggregate->earnPoints(10, 'Snapshot regression award', null, now()->addYear());
            $repository->save($aggregate);
            DB::commit();
            $this->assertSame(1, PointTransaction::where('member_id', $member->id)->where('description', MemberPointLedger::POINTS_LEDGER_BACKFILL_DESCRIPTION)->count());
            $this->assertSame(110, (int) $member->fresh()->points);
            $this->assertSame(110, app(MemberPointLedger::class)->displayPoints($member->fresh()));
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('ledger_writer');
        }
    }

    public function test_locked_balance_and_order_spending_see_consumption_after_an_outer_snapshot(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL repeatable-read snapshots.');
        }

        $member = $this->legacyMember();
        app(MemberPointLedger::class)->sync($member);
        $user = User::findOrFail($member->user_id);
        $order = new Order(['project_id' => $member->project_id, 'user_id' => $user->id]);
        $default = DB::getDefaultConnection();
        config(['database.connections.ledger_writer' => config('database.connections.'.$default)]);
        $writer = DB::connection('ledger_writer');
        DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        DB::beginTransaction();
        try {
            $this->assertSame(100, app(MemberPointLedger::class)->total($member));
            $writer->table('point_transactions')->insert([
                'project_id' => $member->project_id,
                'member_id' => $member->id,
                'type' => 'used',
                'points' => -80,
                'description' => 'Concurrent consumption',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->assertSame(100, app(MemberPointLedger::class)->total($member), 'The ordinary read still sees the earlier balance.');
            $aggregate = app(MemberRepository::class)->findByIdForUpdate((int) $member->id, (int) $member->project_id);
            $this->assertNotNull($aggregate);
            $this->assertSame(20, $aggregate->displayPoints());
            try {
                app(OrderRewardService::class)->consumePointsForOrder($user, $order, 30);
                $this->fail('Spending 30 must be rejected when only 20 points remain.');
            } catch (OrderOperationException $e) {
                $this->assertSame('ポイントが不足しています', $e->getMessage());
            }
            DB::commit();
            $this->assertSame(2, PointTransaction::where('member_id', $member->id)->count());
            $this->assertSame(20, (int) $member->fresh()->points);
            $this->assertSame(20, app(MemberPointLedger::class)->displayPoints($member->fresh()));
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('ledger_writer');
        }
    }

    private function legacyMember(): Member
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);

        return Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id, 'points' => 100]);
    }

    private function assertSingleBackfill(Member $member): void
    {
        $entries = PointTransaction::withoutGlobalScopes()->where('member_id', $member->id)->get();
        $this->assertCount(1, $entries);
        $this->assertSame(MemberPointLedger::POINTS_LEDGER_BACKFILL_DESCRIPTION, $entries->first()->description);
        $this->assertSame(100, (int) $member->fresh()->points);
        $this->assertSame(100, app(MemberPointLedger::class)->displayPoints($member->fresh()));
    }

    private function waitUntil(callable $ready): void
    {
        $deadline = microtime(true) + 5;
        while (! $ready()) {
            if (microtime(true) >= $deadline) {
                throw new \RuntimeException('Timed out waiting for ledger worker.');
            }
            usleep(10000);
        }
    }
}
