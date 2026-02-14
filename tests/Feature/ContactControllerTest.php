<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function testStoreReturnsSuccess(): void
    {
        $this->createProject();

        $response = $this->post('/api/contact?project_key=default', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'message' => 'Hello',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }
}
