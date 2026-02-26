<?php

namespace Tests\Feature;

use App\Models\GalleryCategory;
use App\Models\GalleryItem;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_index_ギャラリーデータを返すこと(): void
    {
        $this->createProject();
        $category = GalleryCategory::factory()->create(['project_id' => 1]);
        GalleryItem::factory()->create(['project_id' => 1, 'category_id' => $category->id]);

        $response = $this->get('/api/gallery?project_key=default');

        $response->assertStatus(200)
            ->assertJsonStructure(['categories', 'items']);
    }
}
