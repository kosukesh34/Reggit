<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
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

        $response = $this->get('/api/products/'.$product->id.'?project_key=default');

        $response->assertStatus(200)
            ->assertJsonPath('id', $product->id);
    }

    public function test_index_商品にオプショングループが含まれること(): void
    {
        $this->createProject();
        $product = Product::factory()->create(['project_id' => 1, 'name' => 'カスタムバーガー']);
        $group = ProductOptionGroup::create([
            'project_id' => 1,
            'product_id' => $product->id,
            'name' => 'サイズ',
            'selection_type' => ProductOptionGroup::TYPE_SINGLE,
            'is_required' => true,
            'min_select' => 1,
            'max_select' => 1,
            'sort_order' => 0,
        ]);
        ProductOption::create([
            'project_id' => 1,
            'group_id' => $group->id,
            'name' => 'L',
            'price_delta' => 80,
            'sort_order' => 0,
        ]);

        $response = $this->get('/api/products?project_key=default');

        $response->assertStatus(200);
        $products = $response->json('products');
        $target = collect($products)->firstWhere('id', $product->id);
        $this->assertNotNull($target);
        $this->assertArrayHasKey('option_groups', $target);
        $this->assertCount(1, $target['option_groups']);
        $this->assertSame('サイズ', $target['option_groups'][0]['name']);
        $this->assertCount(1, $target['option_groups'][0]['options']);
    }
}
