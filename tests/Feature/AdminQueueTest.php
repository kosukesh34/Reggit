<?php

namespace Tests\Feature;

use App\Enums\QueueStatus;
use App\Models\AdminUser;
use App\Models\Project;
use App\Models\QueueEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminQueueTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    private function createAdmin(int $projectId): AdminUser
    {
        return AdminUser::create([
            'login_id' => 'admin',
            'email' => 'admin@example.com',
            'password' => 'password',
            'name' => 'Admin',
            'role' => AdminUser::ROLE_ADMIN,
            'project_id' => $projectId,
        ]);
    }

    public function test_admin_管理者が待ち合わせステータスを更新できること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project->id);
        $entry = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'status' => QueueStatus::WAITING,
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.queue.status', ['project_key' => $project->project_key, 'id' => $entry->id]), [
                'status' => QueueStatus::CALLED,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('queue_entries', [
            'id' => $entry->id,
            'status' => QueueStatus::CALLED,
        ]);
    }
}
