<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_index_商品とカテゴリを返すこと(): void
    {
        $this->createProject();
        Product::factory()->create(['project_id' => 1, 'category' => 'food']);
        Product::factory()->create(['project_id' => 1, 'category' => 'drink']);

        $response = $this->get('/api/products?project_key=default');

        $response->assertStatus(200)
            ->assertJsonStructure(['products', 'categories']);
    }

    public function test_show_商品詳細を返すこと(): void
    {
        $this->createProject();
        $product = Product::factory()->create(['project_id' => 1]);

        $response = $this->get('/api/products/' . $product->id . '?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('id', $product->id);
    }
}
