<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BindProjectFromRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_bind_キー未指定時に最初の有効プロジェクトが使われること(): void
    {
        Project::factory()->create([
            'project_key' => 'alpha',
            'is_active' => true,
        ]);

        $response = $this->get('/liff');

        $response->assertStatus(200);
    }

    public function test_bind_LiffリダイレクトURIからプロジェクトキーが使われること(): void
    {
        Project::factory()->create([
            'project_key' => 'liff-key',
            'is_active' => true,
        ]);

        $redirectUri = urlencode('https://example.com/liff?project_key=liff-key');
        $response = $this->get('/liff?liffRedirectUri=' . $redirectUri);

        $response->assertStatus(200);
    }

    public function test_bind_Liffのstateからプロジェクトキーが使われること(): void
    {
        Project::factory()->create([
            'project_key' => 'state-key',
            'is_active' => true,
        ]);

        $state = urlencode('project_key=state-key');
        $response = $this->get('/liff?state=' . $state);

        $response->assertStatus(200);
    }

    public function test_bind_未定義のプロジェクトキーは存在しないとみなすこと(): void
    {
        Project::factory()->create([
            'project_key' => 'alpha',
            'is_active' => true,
        ]);

        $response = $this->get('/liff?project_key=undefined');

        $response->assertStatus(200);
    }

    public function test_bind_不正なプロジェクトキー指定で404を返すこと(): void
    {
        Project::factory()->create([
            'project_key' => 'alpha',
            'is_active' => true,
        ]);

        $response = $this->get('/liff?project_key=missing');

        $response->assertStatus(404);
    }
}
