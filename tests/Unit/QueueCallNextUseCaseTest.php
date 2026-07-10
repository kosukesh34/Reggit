<?php

namespace Tests\Unit;

use App\Enums\QueueStatus;
use App\Models\Project;
use App\Models\QueueEntry;
use App\UseCases\Queue\QueueCallNextUseCase;
use App\Util\AdminContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueCallNextUseCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        AdminContext::setCurrentProject(null);
        parent::tearDown();
    }

    public function test_call_next_現在のプロジェクトの待ち合わせのみ対象になること(): void
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
        $result = app(QueueCallNextUseCase::class)->handle();

        $this->assertNotNull($result);
        $this->assertSame($target->id, $result->id);

        $this->assertDatabaseHas('queue_entries', [
            'id' => $target->id,
            'status' => QueueStatus::CALLED,
        ]);
    }
}
