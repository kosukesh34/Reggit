<?php

namespace Tests\Unit\UseCases\Queue;

use App\Enums\QueueStatus;
use App\Models\Project;
use App\Models\QueueEntry;
use App\UseCases\Queue\QueueCancelUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueCancelUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_入店済みはキャンセルできずconflictになること(): void
    {
        $project = Project::factory()->create();
        $entry = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'status' => QueueStatus::ENTERED,
        ]);

        $result = app(QueueCancelUseCase::class)->handle((int) $entry->id, (int) $project->id);

        $this->assertTrue($result->isConflict);
        $this->assertNotNull($result->entry);
        $this->assertDatabaseHas('queue_entries', [
            'id' => $entry->id,
            'status' => QueueStatus::ENTERED,
        ]);
    }

    public function test_待ち中はキャンセルできること(): void
    {
        $project = Project::factory()->create();
        $entry = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'status' => QueueStatus::WAITING,
        ]);

        $result = app(QueueCancelUseCase::class)->handle((int) $entry->id, (int) $project->id);

        $this->assertFalse($result->isConflict);
        $this->assertSame(QueueStatus::CANCELLED, $result->entry?->status);
    }
}
