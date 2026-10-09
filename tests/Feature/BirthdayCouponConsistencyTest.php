<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Services\CouponIssueService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BirthdayCouponConsistencyTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_concurrent_birthday_runs_issue_only_one_coupon(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires MySQL and pcntl for independent concurrent connections.');
        }

        $member = $this->birthdayMember();
        $directory = sys_get_temp_dir().'/birthday-coupon-'.bin2hex(random_bytes(8));
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
                        // READ COMMITTED prevents gap locks from masking a missing member lock.
                        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
                        DB::statement('SET SESSION innodb_lock_wait_timeout = 5');
                        $firstRead = true;
                        DB::listen(function ($query) use ($directory, $index, &$firstRead): void {
                            if (! $firstRead || ! str_contains($query->sql, 'from `member_coupons`')) {
                                return;
                            }
                            $firstRead = false;
                            file_put_contents($directory.'/read-'.$index, 'ready');
                            if ($index === 0) {
                                $this->waitUntil(fn () => is_file($directory.'/release'));
                            }
                        });
                        file_put_contents($directory.'/connection-'.$index, (string) DB::selectOne('SELECT CONNECTION_ID() AS id')->id);
                        app(CouponIssueService::class)->issueForBirthday($member);
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
            $this->assertSame(1, MemberCoupon::where('member_id', $member->id)->count());
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

    public function test_birthday_issuance_sees_a_coupon_committed_after_an_outer_snapshot(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL repeatable-read snapshots.');
        }
        $member = $this->birthdayMember();
        $coupon = Coupon::where('project_id', $member->project_id)->firstOrFail();
        $default = DB::getDefaultConnection();
        config(['database.connections.birthday_writer' => config('database.connections.'.$default)]);
        $writer = DB::connection('birthday_writer');
        DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        DB::beginTransaction();
        try {
            $this->assertSame(0, MemberCoupon::where('member_id', $member->id)->count());
            $writer->table('member_coupons')->insert([
                'project_id' => $member->project_id, 'member_id' => $member->id,
                'coupon_id' => $coupon->id, 'status' => MemberCoupon::STATUS_UNUSED,
                'issued_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            app(CouponIssueService::class)->issueForBirthday($member);
            DB::commit();
            $this->assertSame(1, MemberCoupon::where('member_id', $member->id)->count());
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('birthday_writer');
        }
    }

    public function test_all_birthday_coupons_roll_back_when_an_issue_fails(): void
    {
        $member = $this->birthdayMember();
        Coupon::factory()->create([
            'project_id' => $member->project_id, 'is_active' => true,
            'issue_trigger' => Coupon::ISSUE_TRIGGER_BIRTHDAY,
            'start_at' => null, 'end_at' => null,
        ]);
        $active = true;
        $created = 0;
        MemberCoupon::creating(function (MemberCoupon $issued) use (&$active, &$created): void {
            if ($active && ++$created === 2) {
                throw new \RuntimeException('Simulated coupon issue failure');
            }
        });
        try {
            try {
                app(CouponIssueService::class)->issueForBirthday($member);
                $this->fail('The persistence error must propagate.');
            } catch (\RuntimeException $e) {
                $this->assertSame('Simulated coupon issue failure', $e->getMessage());
            }
            $this->assertSame(0, MemberCoupon::where('member_id', $member->id)->count());
        } finally {
            $active = false;
        }
    }

    private function birthdayMember(): Member
    {
        Queue::fake();
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        Coupon::factory()->create([
            'project_id' => $project->id, 'is_active' => true,
            'issue_trigger' => Coupon::ISSUE_TRIGGER_BIRTHDAY,
            'start_at' => null, 'end_at' => null,
        ]);

        return Member::factory()->create(['project_id' => $project->id, 'birthday' => now()->subYears(25)]);
    }

    private function waitUntil(callable $ready): void
    {
        $deadline = microtime(true) + 5;
        while (! $ready()) {
            if (microtime(true) >= $deadline) {
                throw new \RuntimeException('Timed out waiting for lottery worker.');
            }
            usleep(10000);
        }
    }
}
