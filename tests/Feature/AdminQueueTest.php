<?php

namespace Tests\Feature;

use App\Enums\QueueStatus;
use App\Models\AdminUser;
use App\Models\Project;
use App\Models\QueueCallLog;
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

    public function test_admin_一覧は直近1日以内の件数のみ表示すること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project->id);
        $recent = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'member_id' => null,
            'user_id' => null,
            'guest_name' => 'Recent Guest',
            'queue_number' => (int) (now()->format('Ymd').'1'),
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);
        $old = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'member_id' => null,
            'user_id' => null,
            'guest_name' => 'Old Guest',
            'queue_number' => 9901,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.queue', ['project_key' => $project->project_key]));

        $response->assertOk();
        $response->assertViewHas('view', function ($view) use ($recent, $old) {
            $ids = $view->entries->pluck('id');

            return $ids->contains($recent->id) && $ids->contains($old->id) === false;
        });
        $response->assertSee('Recent Guest');
        $response->assertDontSee('Old Guest');
    }

    public function test_admin_入店済みは30分経過後に非表示になること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project->id);
        $recentEntered = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'member_id' => null,
            'user_id' => null,
            'guest_name' => 'Just Entered',
            'status' => QueueStatus::ENTERED,
            'queue_number' => (int) (now()->format('Ymd').'2'),
            'entered_at' => now()->subMinutes(10),
            'created_at' => now()->subHours(1),
            'updated_at' => now()->subMinutes(10),
        ]);
        $oldEntered = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'member_id' => null,
            'user_id' => null,
            'guest_name' => 'Old Entered',
            'status' => QueueStatus::ENTERED,
            'queue_number' => (int) (now()->format('Ymd').'3'),
            'entered_at' => now()->subMinutes(31),
            'created_at' => now()->subHours(1),
            'updated_at' => now()->subMinutes(31),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get(route('admin.queue', ['project_key' => $project->project_key]));

        $response->assertOk();
        $response->assertViewHas('view', function ($view) use ($recentEntered, $oldEntered) {
            $ids = $view->entries->pluck('id');

            return $ids->contains($recentEntered->id) && $ids->contains($oldEntered->id) === false;
        });
        $response->assertSee('Just Entered');
        $response->assertDontSee('Old Entered');
    }

    public function test_admin_呼び出し済みにも再呼び出しできること(): void
    {
        $project = $this->createProject();
        $admin = $this->createAdmin($project->id);
        $entry = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'status' => QueueStatus::CALLED,
            'queue_number' => (int) (now()->format('Ymd').'5'),
            'called_at' => now()->subMinutes(10),
        ]);
        $callLog = QueueCallLog::create([
            'project_id' => $project->id,
            'queue_entry_id' => $entry->id,
            'queue_number' => $entry->queue_number,
            'called_at' => now()->subMinutes(10),
            'push_status' => 'sent',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->post(route('admin.queue.status', ['project_key' => $project->project_key, 'id' => $entry->id]), [
                'status' => QueueStatus::CALLED,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('queue_call_logs', 1);
        $callLog->refresh();
        $this->assertTrue($callLog->called_at->gt(now()->subMinute()));
        $this->assertSame(QueueStatus::CALLED, $entry->fresh()->status);
    }
}
