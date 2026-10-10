<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Member;
use App\Models\MemberCoupon;
use App\Models\Project;
use App\Services\CouponIssueService;
use App\Services\MemberBenefitService;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CouponIssuanceConsistencyTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    #[DataProvider('issuanceMethods')]
    public function test_concurrent_runs_issue_only_one_coupon(string $method, string $trigger): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires MySQL and pcntl for independent concurrent connections.');
        }

        $member = $this->memberWithCoupon($trigger);
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
                        DB::statement('SET SESSION innodb_lock_wait_timeout = 60');
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
                        $service = app(str_starts_with($method, 'bulk') ? MemberBenefitService::class : CouponIssueService::class);
                        file_put_contents($directory.'/initialized-'.$index, 'ready');
                        $this->waitUntil(fn () => is_file($directory.'/start-'.$index));
                        $this->issueCoupons($service, $method, $member);
                        file_put_contents($directory.'/result-'.$index, 'ok');
                        exit(0);
                    } catch (\Throwable $e) {
                        file_put_contents($directory.'/result-'.$index, get_class($e).': '.$e->getMessage());
                        exit(1);
                    }
                }
                $workers[] = $pid;
            }
            // Finish both connections before starting the lock-held barrier.
            DB::selectOne('SELECT 1');
            foreach ([0, 1] as $index) {
                $this->waitUntil(fn () => is_file($directory.'/initialized-'.$index));
            }
            file_put_contents($directory.'/start-0', 'start');
            $this->waitUntil(fn () => is_file($directory.'/read-0'));
            file_put_contents($directory.'/start-1', 'start');
            $this->waitUntil(fn () => is_file($directory.'/connection-1'));
            $connectionId = (int) file_get_contents($directory.'/connection-1');
            $this->waitUntil(function () use ($directory, $connectionId): bool {
                if (is_file($directory.'/result-1') && file_get_contents($directory.'/result-1') !== 'ok') {
                    throw new \RuntimeException(file_get_contents($directory.'/result-1'));
                }
                if (is_file($directory.'/read-1')) {
                    return true;
                }

                return DB::selectOne(
                    'SELECT COUNT(*) AS waiting FROM performance_schema.data_lock_waits AS waits JOIN performance_schema.threads AS threads ON threads.THREAD_ID = waits.REQUESTING_THREAD_ID WHERE threads.PROCESSLIST_ID = ?',
                    [$connectionId],
                )->waiting > 0;
            });
            file_put_contents($directory.'/release', 'release');
            foreach ($workers as $pid) {
                $status = 0;
                $this->waitUntil(function () use ($pid, &$status): bool {
                    $waited = pcntl_waitpid($pid, $status, WNOHANG);
                    if ($waited === -1) {
                        throw new \RuntimeException('Failed waiting for coupon worker '.$pid);
                    }

                    return $waited === $pid;
                });
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

    #[DataProvider('issuanceMethods')]
    public function test_issuance_sees_a_coupon_committed_after_an_outer_snapshot(string $method, string $trigger): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL repeatable-read snapshots.');
        }
        $member = $this->memberWithCoupon($trigger);
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
                'issue_grant_key' => $method === 'bulk_grant' ? implode(':', ['bulk', 'test', $coupon->id, $member->id]) : null,
            ]);
            $this->issueCoupons(app(str_starts_with($method, 'bulk') ? MemberBenefitService::class : CouponIssueService::class), $method, $member);
            DB::commit();
            $this->assertSame(1, MemberCoupon::where('member_id', $member->id)->count());
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('birthday_writer');
        }
    }

    #[DataProvider('issuanceMethods')]
    public function test_all_coupons_roll_back_when_an_issue_fails(string $method, string $trigger): void
    {
        $member = $this->memberWithCoupon($trigger);
        Coupon::factory()->create([
            'project_id' => $member->project_id, 'is_active' => true,
            'issue_trigger' => $trigger,
            'start_at' => null, 'end_at' => null,
        ]);
        if (str_starts_with($method, 'bulk')) {
            Member::factory()->create(['project_id' => $member->project_id]);
        }
        $active = true;
        $created = 0;
        MemberCoupon::creating(function (MemberCoupon $issued) use (&$active, &$created): void {
            if ($active && ++$created === 2) {
                throw new \RuntimeException('Simulated coupon issue failure');
            }
        });
        try {
            try {
                $this->issueCoupons(app(str_starts_with($method, 'bulk') ? MemberBenefitService::class : CouponIssueService::class), $method, $member);
                $this->fail('The persistence error must propagate.');
            } catch (\RuntimeException $e) {
                $this->assertSame('Simulated coupon issue failure', $e->getMessage());
            }
            $this->assertSame(0, MemberCoupon::where('project_id', $member->project_id)->count());
        } finally {
            $active = false;
        }
    }

    public static function issuanceMethods(): array
    {
        return [
            'bulk_grant' => ['bulk_grant', Coupon::ISSUE_TRIGGER_MANUAL],
            'bulk' => ['bulk', Coupon::ISSUE_TRIGGER_MANUAL],
            'birthday' => ['issueForBirthday', Coupon::ISSUE_TRIGGER_BIRTHDAY],
            'registration' => ['issueForFirstRegistration', Coupon::ISSUE_TRIGGER_FIRST_REGISTRATION],
        ];
    }

    private function issueCoupons(CouponIssueService|MemberBenefitService $service, string $method, Member $member): void
    {
        if ($service instanceof MemberBenefitService) {
            $coupon = Coupon::where('project_id', $member->project_id)->firstOrFail();
            $service->issueCouponToMembers((int) $member->project_id, (int) $coupon->id, [], $method === 'bulk_grant' ? 'bulk' : null, $method === 'bulk_grant' ? 'test' : null, true);

            return;
        }

        $service->{$method}($member);
    }

    private function memberWithCoupon(string $trigger): Member
    {
        Queue::fake();
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        Coupon::factory()->create([
            'project_id' => $project->id, 'is_active' => true,
            'issue_trigger' => $trigger,
            'start_at' => null, 'end_at' => null,
        ]);

        return Member::factory()->create(['project_id' => $project->id, 'birthday' => now()->subYears(25)]);
    }

    private function waitUntil(callable $ready): void
    {
        $deadline = hrtime(true) + 60_000_000_000;
        while (! $ready()) {
            if (hrtime(true) >= $deadline) {
                throw new \RuntimeException('Timed out waiting for coupon worker.');
            }
            usleep(10000);
        }
    }
}
