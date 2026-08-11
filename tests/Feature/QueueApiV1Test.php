<?php

namespace Tests\Feature;

use App\Enums\QueueStatus;
use App\Http\Middleware\AuthenticateApiToken;
use App\Models\ApiToken;
use App\Models\Member;
use App\Models\Project;
use App\Models\QueueEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueApiV1Test extends TestCase
{
    use RefreshDatabase;

    private const LINE_USER_ID = 'U44444444444444444444444444444444';

    public function test_モバイル受付を管理画面と_liff_が同じ行として参照すること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true]
        );
        $user = User::factory()->create(['line_user_id' => self::LINE_USER_ID, 'project_id' => $project->id]);
        Member::factory()->create(['user_id' => $user->id, 'project_id' => $project->id]);

        $created = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeader('X-Line-User-Id', self::LINE_USER_ID)
            ->post('/api/v1/queue', ['party_size' => 3]);

        $created->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.queue_entry.party_size', 3);

        $entryId = (int) $created->json('data.queue_entry.id');
        $this->assertDatabaseHas('queue_entries', [
            'id' => $entryId,
            'project_id' => $project->id,
            'user_id' => $user->id,
            'status' => QueueStatus::WAITING,
        ]);

        QueueEntry::whereKey($entryId)->update(['status' => QueueStatus::CALLED, 'called_at' => now()]);

        $mobileStatus = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeader('X-Line-User-Id', self::LINE_USER_ID)
            ->get('/api/v1/queue/me');
        $mobileStatus->assertOk()
            ->assertJsonPath('data.queue_entry.id', $entryId)
            ->assertJsonPath('data.queue_entry.status', QueueStatus::CALLED);

        $liffStatus = $this->withHeader('X-Line-User-Id', self::LINE_USER_ID)
            ->get('/api/queue/me?project_key=default');
        $liffStatus->assertOk()
            ->assertJsonPath('queue_entry.id', $entryId)
            ->assertJsonPath('queue_entry.status', QueueStatus::CALLED);
    }

    public function test_project_key_付き経路から順番待ちをキャンセルできること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true]
        );
        $entry = QueueEntry::factory()->create([
            'project_id' => $project->id,
            'status' => QueueStatus::WAITING,
        ]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->post("/api/v1/default/queue/{$entry->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.queue_entry.status', QueueStatus::CANCELLED);
    }

    public function test_api_トークンと異なる_project_key_は拒否すること(): void
    {
        $tokenProject = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => '', 'is_active' => true]
        );
        Project::firstOrCreate(
            ['project_key' => 'other'],
            ['name' => 'Other', 'description' => '', 'is_active' => true]
        );
        $issued = ApiToken::issue($tokenProject, 'Queue API test');

        $response = $this->withToken($issued['plain'])
            ->get('/api/v1/other/queue/me');

        $response->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'プロジェクトキーが一致しません');
    }
}
