<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BindProjectFromRequestTest extends TestCase
{
    use RefreshDatabase;

    public function testUsesFirstActiveProjectWhenNoKeyProvided(): void
    {
        Project::factory()->create([
            'project_key' => 'alpha',
            'is_active' => true,
        ]);

        $response = $this->get('/liff');

        $response->assertStatus(200);
    }

    public function testUsesProjectKeyFromLiffRedirectUri(): void
    {
        Project::factory()->create([
            'project_key' => 'liff-key',
            'is_active' => true,
        ]);

        $redirectUri = urlencode('https://example.com/liff?project_key=liff-key');
        $response = $this->get('/liff?liffRedirectUri=' . $redirectUri);

        $response->assertStatus(200);
    }

    public function testUsesProjectKeyFromLiffState(): void
    {
        Project::factory()->create([
            'project_key' => 'state-key',
            'is_active' => true,
        ]);

        $state = urlencode('project_key=state-key');
        $response = $this->get('/liff?state=' . $state);

        $response->assertStatus(200);
    }

    public function testTreatsUndefinedProjectKeyAsMissing(): void
    {
        Project::factory()->create([
            'project_key' => 'alpha',
            'is_active' => true,
        ]);

        $response = $this->get('/liff?project_key=undefined');

        $response->assertStatus(200);
    }

    public function testReturns404ForInvalidExplicitProjectKey(): void
    {
        Project::factory()->create([
            'project_key' => 'alpha',
            'is_active' => true,
        ]);

        $response = $this->get('/liff?project_key=missing');

        $response->assertStatus(404);
    }
}
