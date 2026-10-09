<?php

namespace Tests\Feature;

use App\Enums\MemberVisitSource;
use App\Models\Member;
use App\Models\MemberVisit;
use App\Models\MemberVisitItem;
use App\Models\Project;
use App\Services\Smaregi\SmaregiInboundVisitRecorder;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SmaregiInboundVisitConsistencyTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_concurrent_retries_record_each_visit_item_once(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Requires MySQL and pcntl for independent concurrent connections.');
        }

        $member = $this->memberWithVisit();
        $directory = sys_get_temp_dir().'/smaregi-visit-'.bin2hex(random_bytes(8));
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
                            if (! $firstRead || ! str_contains($query->sql, 'from `member_visit_items`')) {
                                return;
                            }
                            $firstRead = false;
                            file_put_contents($directory.'/read-'.$index, 'ready');
                            if ($index === 0) {
                                $this->waitUntil(fn () => is_file($directory.'/release'));
                            }
                        });
                        file_put_contents($directory.'/connection-'.$index, (string) DB::selectOne('SELECT CONNECTION_ID() AS id')->id);
                        app(SmaregiInboundVisitRecorder::class)->record($member, 'sale-1', now(), 300, $this->details());
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
            $this->assertSame(2, MemberVisitItem::count());
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

    public function test_detail_failure_rolls_back_new_visit_and_retry_records_all_items(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $member = Member::factory()->create(['project_id' => $project->id, 'last_visited_at' => null]);
        $active = true;
        $created = 0;
        MemberVisitItem::creating(function () use (&$active, &$created): void {
            if ($active && ++$created === 2) {
                throw new \RuntimeException('Simulated detail persistence failure');
            }
        });
        try {
            try {
                app(SmaregiInboundVisitRecorder::class)->record($member, 'sale-1', now(), 300, $this->details());
                $this->fail('The persistence error must propagate.');
            } catch (\RuntimeException $e) {
                $this->assertSame('Simulated detail persistence failure', $e->getMessage());
            }
            $this->assertSame(0, MemberVisit::count());
            $this->assertSame(0, MemberVisitItem::count());
            $this->assertNull($member->fresh()->last_visited_at);
            $active = false;
            app(SmaregiInboundVisitRecorder::class)->record($member, 'sale-1', now(), 300, $this->details());
            $this->assertSame(1, MemberVisit::count());
            $this->assertSame(['First item', 'Second item'], MemberVisitItem::orderBy('sort_order')->pluck('product_name')->all());
        } finally {
            $active = false;
        }
    }

    public function test_retry_repairs_legacy_partial_items_and_keeps_complete_item_ids(): void
    {
        $member = $this->memberWithVisit();
        $visit = MemberVisit::firstOrFail();
        MemberVisitItem::create([
            'project_id' => $member->project_id, 'member_visit_id' => $visit->id,
            'product_name' => 'First item', 'quantity' => 1,
            'unit_price' => 100, 'amount' => 100, 'sort_order' => 0,
        ]);
        app(SmaregiInboundVisitRecorder::class)->record($member, 'sale-1', now(), 300, $this->details());
        $this->assertSame(['First item', 'Second item'], $visit->items()->pluck('product_name')->all());
        $ids = $visit->items()->pluck('id')->all();
        app(SmaregiInboundVisitRecorder::class)->record($member, 'sale-1', now(), 300, $this->details());
        $this->assertSame($ids, $visit->items()->pluck('id')->all());
    }

    public function test_fractional_prices_keep_item_ids_on_retry(): void
    {
        $member = $this->memberWithVisit();
        $details = [['productName' => 'Fractional item', 'quantity' => 3, 'salesPrice' => 0.1]];
        $visit = app(SmaregiInboundVisitRecorder::class)->record($member, 'sale-1', now(), 0.3, $details);
        $ids = $visit->items()->pluck('id')->all();
        app(SmaregiInboundVisitRecorder::class)->record($member, 'sale-1', now(), 0.3, $details);
        $this->assertSame($ids, $visit->items()->pluck('id')->all());
    }

    private function memberWithVisit(): Member
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $member = Member::factory()->create(['project_id' => $project->id]);
        MemberVisit::create([
            'project_id' => $project->id, 'member_id' => $member->id,
            'source' => MemberVisitSource::SMAREGI, 'smaregi_transaction_head_id' => 'sale-1',
            'visited_at' => now(), 'amount' => 300,
        ]);

        return $member;
    }

    private function details(): array
    {
        return [
            ['productName' => 'First item', 'quantity' => 1, 'salesPrice' => 100],
            ['productName' => 'Second item', 'quantity' => 1, 'salesPrice' => 200],
        ];
    }

    private function waitUntil(callable $ready): void
    {
        $deadline = microtime(true) + 5;
        while (! $ready()) {
            if (microtime(true) >= $deadline) {
                throw new \RuntimeException('Timed out waiting for visit worker.');
            }
            usleep(10000);
        }
    }
}
