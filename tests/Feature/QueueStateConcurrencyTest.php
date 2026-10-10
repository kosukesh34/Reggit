<?php

namespace Tests\Feature;

use App\Domain\Queue\Aggregates\QueueEntry as QueueEntryAggregate;
use App\Domain\Queue\Repositories\QueueEntryRepository;
use App\Enums\QueueStatus;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Services\Admin\QueueAdminService;
use App\UseCases\Queue\QueueCallNextUseCase;
use App\UseCases\Queue\QueueCancelUseCase;
use App\UseCases\Queue\QueueEnterUseCase;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueueStateConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    private function withCommittedChange(string $status, callable $action): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL repeatable-read snapshots.');
        }
        $project = Project::factory()->create();
        $entry = QueueEntry::factory()->create(['project_id' => $project->id, 'status' => QueueStatus::WAITING, 'queue_number' => 1]);
        $next = QueueEntry::factory()->create(['project_id' => $project->id, 'status' => QueueStatus::WAITING, 'queue_number' => 2]);
        config(['database.connections.queue_writer' => config('database.connections.'.DB::getDefaultConnection())]);
        $writer = DB::connection('queue_writer');
        DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        DB::beginTransaction();
        try {
            $this->assertSame(QueueStatus::WAITING, QueueEntry::findOrFail($entry->id)->status);
            $writer->table('queue_entries')->where('id', $entry->id)->update([
                'status' => $status,
                'entered_at' => $status === QueueStatus::ENTERED ? now() : null,
                'called_at' => $status === QueueStatus::CALLED ? now() : null,
            ]);
            $this->assertSame(QueueStatus::WAITING, QueueEntry::findOrFail($entry->id)->status);
            $action($entry, $next);
            DB::commit();
            $this->assertDatabaseHas('queue_entries', ['id' => $entry->id, 'status' => $status]);
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            DB::purge('queue_writer');
        }
    }

    public function test_cancel_cannot_overwrite_a_committed_entry(): void
    {
        $this->withCommittedChange(QueueStatus::ENTERED, function ($entry): void {
            $result = app(QueueCancelUseCase::class)->handle((int) $entry->id, (int) $entry->project_id);
            $this->assertTrue($result->isConflict);
        });
        $this->assertDatabaseMissing('queue_entries', ['queue_number' => 1, 'entered_at' => null]);
    }

    public function test_entry_cannot_revive_a_committed_cancellation(): void
    {
        $this->withCommittedChange(QueueStatus::CANCELLED, function ($entry): void {
            $rejected = false;
            try {
                app(QueueEnterUseCase::class)->handle((int) $entry->id, (int) $entry->project_id);
            } catch (\InvalidArgumentException) {
                $rejected = true;
            }
            $this->assertTrue($rejected);
        });
    }

    public function test_call_next_skips_a_call_committed_after_its_snapshot(): void
    {
        $this->withCommittedChange(QueueStatus::CALLED, function ($entry, $next): void {
            $this->mock(QueueAdminService::class, function ($mock) use ($next): void {
                $mock->shouldReceive('notifyCalled')->once()->with((int) $next->id);
            });
            $result = app(QueueCallNextUseCase::class)->handle((int) $entry->project_id);
            $this->assertNotNull($result);
            $this->assertSame((int) $next->id, $result->id);
        });
        $this->assertDatabaseCount('queue_entries', 2);
        $this->assertSame(2, QueueEntry::where('status', QueueStatus::CALLED)->count());
    }

    public function test_state_lock_is_held_until_the_change_has_been_saved(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL row locks.');
        }
        $project = Project::factory()->create();
        $entry = QueueEntry::factory()->create(['project_id' => $project->id, 'status' => QueueStatus::WAITING]);
        config(['database.connections.queue_writer' => config('database.connections.'.DB::getDefaultConnection())]);
        $writer = DB::connection('queue_writer');
        $writer->statement('SET SESSION innodb_lock_wait_timeout = 1');
        try {
            $result = app(QueueEntryRepository::class)->update((int) $entry->id, (int) $project->id,
                function (QueueEntryAggregate $aggregate) use ($writer, $entry): void {
                    $blocked = false;
                    try {
                        $writer->table('queue_entries')->where('id', $entry->id)->update(['status' => QueueStatus::CANCELLED]);
                    } catch (QueryException $exception) {
                        $blocked = true;
                        $this->assertSame(1205, $exception->errorInfo[1]);
                    }
                    $this->assertTrue($blocked, 'A concurrent writer must wait while the domain state is changed.');
                    $aggregate->markAsEntered(now());
                },
            );
            $this->assertNotNull($result);
            $this->assertDatabaseHas('queue_entries', ['id' => $entry->id, 'status' => QueueStatus::ENTERED]);
        } finally {
            DB::purge('queue_writer');
        }
    }
}
