<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Project;
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
}
