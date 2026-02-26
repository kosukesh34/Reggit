<?php

namespace Tests\Unit;

use App\Models\Project;
use App\Models\QueueEntry;
use App\UseCases\Queue\QueueStatsService;
use App\Enums\QueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_getStats_統計がプロジェクト単位で集計されること(): void
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

        QueueEntry::factory()->count(2)->create([
            'project_id' => $project->id,
            'status' => QueueStatus::WAITING,
        ]);
        QueueEntry::factory()->count(3)->create([
            'project_id' => $other->id,
            'status' => QueueStatus::WAITING,
        ]);

        $service = new QueueStatsService();
        $stats = $service->getStats($project->id, null);

        $this->assertSame(2, $stats['waiting_count']);
    }

    public function test_getStats_前の待ち人数が計算されること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );

        QueueEntry::factory()->create([
            'project_id' => $project->id,
            'status' => QueueStatus::WAITING,
            'queue_number' => 1,
        ]);
        QueueEntry::factory()->create([
            'project_id' => $project->id,
            'status' => QueueStatus::WAITING,
            'queue_number' => 2,
        ]);

        $service = new QueueStatsService();
        $stats = $service->getStats($project->id, 2);

        $this->assertSame(1, $stats['ahead_count']);
    }
}
