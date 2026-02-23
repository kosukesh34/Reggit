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

    public function testIndexReturnsAnnouncements(): void
    {
        $project = $this->createProject();
        Announcement::factory()->create(['project_id' => $project->id]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->get('/api/v1/announcements');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['announcements']]);
    }

    public function testIndexContainsReadStateForUser(): void
    {
        $project = $this->createProject();
        $user = User::factory()->create([
            'project_id' => $project->id,
            'line_user_id' => 'line-user-read-state',
        ]);
        $announcement = Announcement::factory()->create(['project_id' => $project->id]);
        AnnouncementRead::create([
            'project_id' => $project->id,
            'announcement_id' => $announcement->id,
            'user_id' => $user->id,
            'read_at' => now(),
        ]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->withHeader('X-Line-User-Id', 'line-user-read-state')
            ->get('/api/v1/announcements');

        $response->assertStatus(200)
            ->assertJsonPath('data.announcements.0.is_read', true);
    }

    public function testMarkReadCreatesReadRecord(): void
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
