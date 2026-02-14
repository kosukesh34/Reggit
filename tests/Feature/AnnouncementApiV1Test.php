<?php

namespace Tests\Feature;

use App\Http\Middleware\AuthenticateApiToken;
use App\Models\Announcement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementApiV1Test extends TestCase
{
    use RefreshDatabase;

    public function testIndexReturnsAnnouncements(): void
    {
        Announcement::factory()->create(['project_id' => 1]);

        $response = $this->withoutMiddleware(AuthenticateApiToken::class)
            ->get('/api/v1/announcements');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['announcements']]);
    }
}
