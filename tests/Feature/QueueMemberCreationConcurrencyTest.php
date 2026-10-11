<?php

namespace Tests\Feature;

use App\Domain\Settings\Repositories\QueueSettings;
use App\Enums\QueueStatus;
use App\Models\Member;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Models\User;
use App\UseCases\Queue\Data\QueueStoreInput;
use App\UseCases\Queue\QueueStoreUseCase;
use App\Util\AdminContext;
use App\Util\StoreContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class QueueMemberCreationConcurrencyTest extends TestCase
{
    use DatabaseTruncation;

    #[BeforeClass]
    public static function resetMigrationBeforeClass(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    #[AfterClass]
    public static function resetMigrationAfterClass(): void
    {
        RefreshDatabaseState::$migrated = false;
    }

    public function test_number_collision_retries_with_a_fresh_committed_snapshot(): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $other = User::factory()->create(['project_id' => $project->id]);
        $this->configureWriter();
        $storeId = app(StoreContext::class)->resolveForWrite((int) $project->id);
        $inserted = false;
        $firstNumber = null;
        Event::listen('eloquent.creating: '.QueueEntry::class, function (QueueEntry $entry) use ($other, $storeId, &$inserted, &$firstNumber): void {
            if ($inserted) {
                return;
            }
            $inserted = true;
            $firstNumber = $entry->queue_number;
            $writer = DB::connection('review_writer');
            $this->assertSame(0, $writer->transactionLevel());
            $writer->table('queue_entries')->insert([
                'project_id' => $entry->project_id, 'store_id' => $storeId, 'user_id' => $other->id,
                'party_size' => 1, 'status' => QueueStatus::WAITING,
                'queue_number' => $entry->queue_number, 'qr_token' => Str::random(32),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        try {
            $result = app(QueueStoreUseCase::class)->handle(new QueueStoreInput($user, 2, null, null, null, null));
        } finally {
            DB::purge('review_writer');
        }
        $this->assertTrue($inserted);
        $this->assertFalse($result->requiresMembership);
        $this->assertNotNull($result->entry);
        $this->assertSame($firstNumber + 1, $result->entry->queueNumber);
        $this->assertDatabaseCount('queue_entries', 2);
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_member_ownership_remains_locked_until_ticket_is_saved(bool $deleted): void
    {
        $project = Project::factory()->create();
        AdminContext::setCurrentProject($project);
        $user = User::factory()->create(['project_id' => $project->id]);
        $member = Member::factory()->create(['project_id' => $project->id, 'user_id' => $user->id]);
        $other = User::factory()->create(['project_id' => $project->id]);
        $this->configureWriter();
        $change = $deleted ? ['deleted_at' => now()] : ['user_id' => $other->id];
        $blocked = false;
        $settings = $this->mock(QueueSettings::class);
        $settings->shouldReceive('getQueueAvgServeMinutes')->once()->andReturn(5);
        $settings->shouldReceive('getQueueRequireMembership')->once()->andReturnUsing(function () use ($member, $change, &$blocked): bool {
            $writer = DB::connection('review_writer');
            $writer->statement('SET SESSION innodb_lock_wait_timeout = 1');
            try {
                $writer->table('members')->where('id', $member->id)->update($change);
            } catch (QueryException $exception) {
                $this->assertSame(1205, (int) $exception->errorInfo[1]);
                $blocked = true;
            }

            return true;
        });
        try {
            $result = app(QueueStoreUseCase::class)->handle(new QueueStoreInput($user, 2, null, null, null, null));
            $this->assertTrue($blocked);
            $this->assertFalse($result->requiresMembership);
            $this->assertDatabaseHas('queue_entries', ['user_id' => $user->id, 'member_id' => $member->id]);
            $this->assertSame(1, DB::connection('review_writer')->table('members')->where('id', $member->id)->update($change));
        } finally {
            DB::purge('review_writer');
        }
    }

    private function configureWriter(): void
    {
        config(['database.connections.review_writer' => config('database.connections.'.DB::getDefaultConnection())]);
    }
}
