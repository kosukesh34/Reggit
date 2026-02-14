<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiffControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function testLiffIndexReturns200WhenProjectExists(): void
    {
        $this->createProject();

        $response = $this->get('/liff?project_key=default');

        $response->assertStatus(200);
    }

    public function testLiffIndexUsesFirstActiveProjectWhenNoKey(): void
    {
        $this->createProject();

        $response = $this->get('/liff');

        $response->assertStatus(200);
    }
}
