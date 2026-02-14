<?php

namespace Tests\Unit;

use App\Enums\QueueStatus;
use App\Models\Project;
use App\Models\QueueEntry;
use App\UseCases\Queue\QueueCallNextUseCase;
use App\UseCases\Queue\QueueStatsService;
use App\UseCases\Queue\Dto\QueueEntryDtoFactory;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueCallNextUseCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_next_only_affects_current_project(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
        $other = Project::create([
            'project_key' => 'other',
            'name' => 'Other Project',
            'description' => 'Test',
            'is_active' => true,
        ]);

        $target = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'status' => QueueStatus::WAITING,
            'queue_number' => 1,
        ]);
        QueueEntry::factory()->create([
            'project_id' => $other->id,
            'status' => QueueStatus::WAITING,
            'queue_number' => 1,
        ]);

        AdminContext::setCurrentProject($project);
        $useCase = new QueueCallNextUseCase(new QueueStatsService(), new QueueEntryDtoFactory());
        $result = $useCase->handle();

        $this->assertNotNull($result);
        $this->assertSame($target->id, $result->id);

        $this->assertDatabaseHas('queue_entries', [
            'id' => $target->id,
            'status' => QueueStatus::CALLED,
        ]);
    }
}
