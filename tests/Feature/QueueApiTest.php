<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Models\User;
use App\Enums\QueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueApiTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_store_creates_queue_entry(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => 'line-queue-1', 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);

        $response = $this->withHeaders(['X-Line-User-Id' => 'line-queue-1'])
            ->post('/api/queue?project_key=default', ['party_size' => 2]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'queue_entry' => ['id', 'queue_number', 'party_size', 'status']]);

        $this->assertDatabaseHas('queue_entries', [
            'project_id' => 1,
            'party_size' => 2,
            'status' => QueueStatus::WAITING,
        ]);

        $entry = QueueEntry::first();
        $this->assertNotNull($entry);
        $this->assertNotNull($entry->qr_token);
        $this->assertSame($user->id, $entry->user_id);
    }

    public function test_me_returns_current_queue_entry(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => 'line-queue-2', 'project_id' => 1]);
        $member = Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'member_id' => $member->id,
            'status' => QueueStatus::WAITING,
            'queue_number' => 3,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => 'line-queue-2'])
            ->get('/api/queue/me?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.id', $entry->id);
    }

    public function test_cancel_updates_status(): void
    {
        $this->createProject();
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'status' => QueueStatus::WAITING,
        ]);

        $response = $this->post("/api/queue/{$entry->id}/cancel?project_key=default");

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.status', 'cancelled');

        $this->assertDatabaseHas('queue_entries', [
            'id' => $entry->id,
            'status' => QueueStatus::CANCELLED,
        ]);
    }

    public function test_call_next_marks_called(): void
    {
        $this->createProject();
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'status' => QueueStatus::WAITING,
            'queue_number' => 1,
        ]);

        $response = $this->post('/api/queue/call-next?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.id', $entry->id)
            ->assertJsonPath('queue_entry.status', QueueStatus::CALLED);
    }

    public function test_qr_status_requires_line_login(): void
    {
        $this->createProject();
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'qr_token' => 'token-no-line',
        ]);

        $response = $this->get("/api/queue/qr/{$entry->qr_token}?project_key=default");

        $response->assertStatus(403)
            ->assertJsonPath('error', 'LINEログインが必要です');
    }

    public function test_qr_status_binds_user(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => 'line-queue-qr', 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => null,
            'member_id' => null,
            'qr_token' => 'token-bind',
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => 'line-queue-qr'])
            ->get("/api/queue/qr/{$entry->qr_token}?project_key=default");

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.id', $entry->id)
            ->assertJsonPath('queue_entry.qr_token', $entry->qr_token);

        $entry->refresh();
        $this->assertSame($user->id, $entry->user_id);
    }

    public function test_qr_status_blocks_multiple_entries_for_same_user(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => 'line-queue-multi', 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => $user->id,
            'status' => QueueStatus::WAITING,
            'qr_token' => 'token-existing',
        ]);
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => null,
            'member_id' => null,
            'qr_token' => 'token-new',
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => 'line-queue-multi'])
            ->get("/api/queue/qr/{$entry->qr_token}?project_key=default");

        $response->assertStatus(403)
            ->assertJsonPath('error', '同じLINEアカウントで複数登録できません');
    }

    public function test_qr_status_blocks_other_user(): void
    {
        $this->createProject();
        $owner = User::factory()->create(['line_user_id' => 'line-queue-owner', 'project_id' => 1]);
        $other = User::factory()->create(['line_user_id' => 'line-queue-other', 'project_id' => 1]);
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => $owner->id,
            'qr_token' => 'token-locked',
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => 'line-queue-other'])
            ->get("/api/queue/qr/{$entry->qr_token}?project_key=default");

        $response->assertStatus(403)
            ->assertJsonPath('error', '別のLINEアカウントで登録済みです');
    }

    public function test_qr_status_accepts_queue_id(): void
    {
        $this->createProject();
        $user = User::factory()->create(['line_user_id' => 'line-queue-id', 'project_id' => 1]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => 1]);
        $entry = QueueEntry::factory()->create([
            'project_id' => 1,
            'user_id' => null,
            'qr_token' => null,
        ]);

        $response = $this->withHeaders(['X-Line-User-Id' => 'line-queue-id'])
            ->get("/api/queue/qr/{$entry->id}?project_key=default");

        $response->assertStatus(200)
            ->assertJsonPath('queue_entry.id', $entry->id);
        $entry->refresh();
        $this->assertNotNull($entry->qr_token);
    }
}
