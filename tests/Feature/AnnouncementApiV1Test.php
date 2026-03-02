<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementApiV1Test extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['id' => 1],
            ['project_key' => 'default', 'name' => 'Default', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_index_お知らせ一覧を返すこと(): void
    {
        $project = $this->createProject();
        Announcement::factory()->create(['project_id' => $project->id]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->get('/api/v1/announcements');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['announcements']]);
    }

    public function test_index_ユーザーごとの既読状態が含まれること(): void
    {
        $project = $this->createProject();
        $lineUserId = 'U00000000000000000000000000000003';
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => $lineUserId,
        ]);
        $announcement = Announcement::factory()->create(['project_id' => $project->id]);
        AnnouncementRead::create([
            'project_id' => $project->id,
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeader('X-Line-User-Id', $lineUserId)
            ->get('/api/v1/announcements');

        $response->assertStatus(200)
            ->assertJsonPath('data.announcements.0.is_read', true);
    }

    public function test_mark_read_既読レコードが作成されること(): void
    {
        $project = $this->createProject();
        $announcement = Announcement::factory()->create(['project_id' => $project->id]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeader('X-Line-User-Id', 'line-user-mark-read')
            ->post("/api/v1/announcements/{$announcement->id}/read");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('announcement_reads', [
            'project_id' => $project->id,
            'announcement_id' => $announcement->id,
        ]);
    }
}
