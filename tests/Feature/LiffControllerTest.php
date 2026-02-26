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

    public function test_liff_プロジェクトが存在するとき200を返すこと(): void
    {
        $this->createProject();

        $response = $this->get('/liff?project_key=default');

        $response->assertStatus(200);
    }

    public function test_liff_キー未指定時は最初の有効プロジェクトが使われること(): void
    {
        $this->createProject();

        $response = $this->get('/liff');

        $response->assertStatus(200);
    }
}
