<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppConfigApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_app_config_有効なプロジェクトキーで設定を返すこと(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'テスト店舗', 'description' => 'Test', 'is_active' => true]
        );
        $project->update(['name' => 'テスト店舗', 'is_active' => true]);

        $response = $this->getJson('/api/v1/app-config?project_key=default');

        $response->assertOk();
        $response->assertJsonPath('project_key', 'default');
        $response->assertJsonPath('project_name', 'テスト店舗');
        $response->assertJsonStructure([
            'project_key',
            'project_name',
            'tabs',
            'feature_flags',
            'theme',
            'header',
            'liff_tab_layouts',
            'liff_tab_settings',
        ]);
    }
}
