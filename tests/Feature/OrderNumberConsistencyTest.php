<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Project;
use App\Models\User;
use App\Services\OrderNumberAssigner;
use App\Support\DailySequenceNumber;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OrderNumberConsistencyTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    /** @return array<string, array{string, int, bool}> */
    public static function allocationCases(): array
    {
        return [
            'RC empty day' => ['READ COMMITTED', 0, false],
            'RR empty day' => ['REPEATABLE READ', 0, false],
            'RC legacy day' => ['READ COMMITTED', 9, false],
            'RR legacy day' => ['REPEATABLE READ', 9, false],
            'RC existing counter' => ['READ COMMITTED', 9, true],
            'RR existing counter' => ['REPEATABLE READ', 9, true],
        ];
    }

    #[DataProvider('allocationCases')]
    public function test_concurrent_first_numbers_both_succeed_with_distinct_numbers(string $isolation, int $legacy, bool $warm): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires MySQL and pcntl.');
        }
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $orders = collect([0, 1])->map(fn () => Order::create(['project_id' => $project->id, 'user_id' => $user->id, 'order_number' => null, 'total_amount' => 100, 'status' => 'pending']));
        if ($legacy > 0) {
            Order::create(['project_id' => $project->id, 'user_id' => $user->id, 'order_number' => DailySequenceNumber::build($legacy), 'total_amount' => 100, 'status' => 'pending']);
        }
        if ($warm) {
            DB::table('order_daily_sequences')->insert(['project_id' => $project->id, 'date' => today()->toDateString(), 'last_sequence' => $legacy]);
        }
        $directory = sys_get_temp_dir().'/order-number-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700);
        DB::disconnect();
        $workers = [];
        try {
            foreach ($orders as $index => $order) {
                $pid = pcntl_fork();
                $this->assertNotSame(-1, $pid);
                if ($pid === 0) {
                    try {
                        DB::purge();
                        DB::statement('SET SESSION TRANSACTION ISOLATION LEVEL '.$isolation);
                        DB::statement('SET SESSION innodb_lock_wait_timeout = 60');
                        file_put_contents($directory.'/connection-'.$index, (string) DB::selectOne('SELECT CONNECTION_ID() AS id')->id);
                        DB::connection()->beforeExecuting(function ($sql) use ($directory, $index): void {
                            file_put_contents($directory.'/progress-'.$index, $sql);
                        });
                        $firstScan = true;
                        DB::listen(function ($query) use ($directory, $index, &$firstScan): void {
                            if (str_contains($query->sql, 'select `order_number`')) {
                                file_put_contents($directory.'/plan-'.$index, json_encode([$query->sql, $query->bindings], JSON_THROW_ON_ERROR));
                            }
                            if (! $firstScan || (! (str_starts_with($query->sql, 'insert') && str_contains($query->sql, 'order_daily_sequences')) && ! str_contains($query->sql, 'select `order_number`'))) {
                                return;
                            }
                            $firstScan = false;
                            file_put_contents($directory.'/ready-'.$index, 'ready');
                            $this->waitUntil(fn () => is_file($directory.'/release'));
                        });
                        DB::transaction(function () use ($order, $project, $user, $directory, $index): void {
                            // Simulate an outer order transaction already holding a project FK lock.
                            Order::create(['project_id' => $project->id, 'user_id' => $user->id, 'order_number' => null, 'total_amount' => 100, 'status' => 'pending']);
                            file_put_contents($directory.'/initialized-'.$index, 'ready');
                            $this->waitUntil(fn () => is_file($directory.'/start-'.$index));
                            app(OrderNumberAssigner::class)->assignIfMissing((int) $order->id, (int) $project->id);
                        });
                        file_put_contents($directory.'/result-'.$index, 'ok');
                        exit(0);
                    } catch (\Throwable $e) {
                        file_put_contents($directory.'/result-'.$index, get_class($e).': '.$e->getMessage());
                        exit(1);
                    }
                }
                $workers[] = $pid;

            }
            // Reconnect the parent only after both forks, then finish all setup
            // before pausing worker 0 while it holds the counter lock.
            DB::selectOne('SELECT 1');
            foreach ([0, 1] as $index) {
                $this->waitUntil(fn () => is_file($directory.'/initialized-'.$index));
            }
            file_put_contents($directory.'/start-0', 'start');
            $this->waitUntil(fn () => is_file($directory.'/ready-0'));
            file_put_contents($directory.'/start-1', 'start');
            $second = 1;
            $this->waitUntil(fn () => is_file($directory.'/connection-'.$second));
            $connectionId = (int) file_get_contents($directory.'/connection-'.$second);
            $this->waitUntil(function () use ($directory, $second, $connectionId): bool {
                if (is_file($directory.'/result-'.$second)) {
                    throw new \RuntimeException(file_get_contents($directory.'/result-'.$second));
                }

                return is_file($directory.'/ready-'.$second) || DB::selectOne(
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
                        throw new \RuntimeException('Failed waiting for worker '.$pid.': '.pcntl_strerror(pcntl_get_last_error()));
                    }

                    return $waited === $pid;
                });
            }
            $workers = [];
            foreach ([0, 1] as $index) {
                $this->assertSame('ok', file_get_contents($directory.'/result-'.$index));
            }
            $numbers = Order::query()->whereIn('id', $orders->pluck('id')->all())->pluck('order_number')->all();
            $this->assertNotContains(null, $numbers);
            $this->assertCount(2, array_unique($numbers));
            $this->assertEqualsCanonicalizing([DailySequenceNumber::build($legacy + 1), DailySequenceNumber::build($legacy + 2)], $numbers);
            if (! $warm) {
                $planFile = is_file($directory.'/plan-0') ? $directory.'/plan-0' : $directory.'/plan-1';
                [$sql, $bindings] = json_decode(file_get_contents($planFile), true, flags: JSON_THROW_ON_ERROR);
                $plan = DB::selectOne('EXPLAIN '.$sql, $bindings);
                $this->assertSame('orders_project_id_order_number_unique', $plan->key);
            }
        } catch (\Throwable $e) {
            $files = [];
            foreach (glob($directory.'/*') ?: [] as $file) {
                $files[basename($file)] = file_get_contents($file);
            }
            $transactions = DB::select('SELECT trx_mysql_thread_id, trx_state, trx_query FROM information_schema.innodb_trx');
            throw new \RuntimeException($e->getMessage().' '.json_encode(['files' => $files, 'transactions' => $transactions], JSON_THROW_ON_ERROR), 0, $e);
        } finally {
            foreach ($workers as $pid) {
                posix_kill($pid, SIGKILL);
                pcntl_waitpid($pid, $status);
            }
            foreach (glob($directory.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($directory);
            DB::purge();
        }
    }

    public function test_outer_rollback_reverts_order_number_and_counter(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create(['project_id' => $project->id, 'user_id' => $user->id, 'order_number' => null, 'total_amount' => 100, 'status' => 'pending']);
        DB::beginTransaction();
        try {
            $this->assertSame(DailySequenceNumber::build(1), app(OrderNumberAssigner::class)->assignIfMissing((int) $order->id, (int) $project->id));
        } finally {
            DB::rollBack();
        }
        $this->assertNull($order->fresh()->order_number);
        $this->assertSame(0, DB::table('order_daily_sequences')->count());
        $this->assertSame(DailySequenceNumber::build(1), app(OrderNumberAssigner::class)->assignIfMissing((int) $order->id, (int) $project->id));
        $this->assertSame(DailySequenceNumber::build(1), app(OrderNumberAssigner::class)->assignIfMissing((int) $order->id, (int) $project->id));
        $this->assertSame(1, (int) DB::table('order_daily_sequences')->sole()->last_sequence);
    }

    public function test_bootstrap_reads_legacy_number_committed_after_outer_snapshot(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL repeatable-read snapshots.');
        }
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $order = Order::create(['project_id' => $project->id, 'user_id' => $user->id, 'order_number' => null, 'total_amount' => 100, 'status' => 'pending']);
        config(['database.connections.number_writer' => config('database.connections.'.DB::getDefaultConnection())]);
        DB::beginTransaction();
        try {
            Order::query()->count(); // Establish the outer transaction's older snapshot.
            DB::connection('number_writer')->table('orders')->insert([
                'project_id' => $project->id, 'store_id' => $order->store_id, 'user_id' => $user->id,
                'order_number' => DailySequenceNumber::build(9), 'total_amount' => 100, 'status' => 'pending',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $this->assertSame(DailySequenceNumber::build(10), app(OrderNumberAssigner::class)->assignIfMissing((int) $order->id, (int) $project->id));
        } finally {
            DB::rollBack();
            DB::purge('number_writer');
        }
    }

    private function waitUntil(callable $condition): void
    {
        // Bound worker setup as well as the lock barriers on slower CI hosts.
        $deadline = hrtime(true) + 60_000_000_000;
        while (! $condition()) {
            if (hrtime(true) > $deadline) {
                throw new \RuntimeException('Timed out waiting for concurrent numbering barrier.');
            }
            usleep(10000);
        }
    }
}
