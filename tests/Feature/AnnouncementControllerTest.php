<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function testIndexReturnsAnnouncements(): void
    {
        $this->createProject();
        Announcement::factory()->create(['project_id' => 1]);

        $response = $this->get('/api/announcements?project_key=default');

        $response->assertStatus(200)
            ->assertJsonStructure(['announcements']);
    }

    public function testMarkReadEndpointReturnsSuccess(): void
    {
        $this->createProject();
        $announcement = Announcement::factory()->create(['project_id' => 1]);
        User::factory()->create([
            'project_id' => 1,
            'line_user_id' => 'line-user-web-read',
        ]);

        $response = $this->withHeader('X-Line-User-Id', 'line-user-web-read')
            ->post("/api/announcements/{$announcement->id}/read?project_key=default");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('announcement_reads', [
            'project_id' => 1,
            'announcement_id' => $announcement->id,
        ]);
    }
}
