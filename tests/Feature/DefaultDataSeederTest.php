<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Project;
use App\Services\Admin\ProductDeduplicationService;
use App\Services\Smaregi\SmaregiProductSyncService;
use Database\Seeders\DefaultDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class DefaultDataSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_products_only_同名商品を重複作成しないこと(): void
    {
        $project = Project::factory()->create();
        $seeder = app(DefaultDataSeeder::class);

        $seeder->seedProductsOnly($project);
        $seeder->seedProductsOnly($project);

        $this->assertSame(16, Product::withoutGlobalScopes()->where('project_id', $project->id)->count());
    }

    public function test_deduplicate_for_project_同名商品を1件に統合すること(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique('products_project_name_unique');
        });

        $project = Project::factory()->create();
        Product::withoutGlobalScopes()->create([
            'project_id' => $project->id,
            'name' => 'フリーライト ハンバーガー',
            'price' => 680,
            'category' => 'food',
            'is_available' => true,
            'stock' => 100,
            'smaregi_product_id' => '8000001',
        ]);
        Product::withoutGlobalScopes()->create([
            'project_id' => $project->id,
            'name' => 'フリーライト ハンバーガー',
            'price' => 680,
            'category' => 'food',
            'is_available' => true,
            'stock' => 0,
        ]);

        $deleted = app(ProductDeduplicationService::class)->deduplicateForProject((int) $project->id);

        $this->assertSame(1, $deleted);
        $remaining = Product::withoutGlobalScopes()->where('project_id', $project->id)->get();
        $this->assertCount(1, $remaining);
        $this->assertSame('8000001', $remaining->first()->smaregi_product_id);
        $this->assertSame(100, $remaining->first()->stock);
    }

    public function test_smaregi_sync_同名の既存商品に紐付けて重複を作らないこと(): void
    {
        $project = Project::factory()->create();
        Product::withoutGlobalScopes()->create([
            'project_id' => $project->id,
            'name' => 'フリーライト ハンバーガー',
            'price' => 680,
            'category' => 'food',
            'is_available' => true,
            'stock' => 100,
        ]);

        $sync = app(SmaregiProductSyncService::class);
        $method = new \ReflectionMethod($sync, 'upsertProductFromSmaregi');
        $method->setAccessible(true);
        $method->invoke($sync, (int) $project->id, '8000999', [
            'name' => 'フリーライト ハンバーガー',
            'description' => '更新',
            'price' => 680,
            'category' => 'food',
            'is_available' => true,
        ]);

        $products = Product::withoutGlobalScopes()->where('project_id', $project->id)->get();
        $this->assertCount(1, $products);
        $this->assertSame('8000999', $products->first()->smaregi_product_id);
    }

    public function test_deduplicate_smaregi_products_同名の重複商品を削除すること(): void
    {
        config([
            'smaregi.client_id' => 'test_client_id',
            'smaregi.client_secret' => 'test_client_secret',
            'smaregi.id_base_url' => 'https://id.smaregi.dev',
            'smaregi.api_base_url' => 'https://api.smaregi.dev',
        ]);

        $project = Project::factory()->create();
        \App\Models\ShopSetting::setValue('smaregi_contract_id', 'test_contract_sb', 'string', (int) $project->id);
        Product::withoutGlobalScopes()->create([
            'project_id' => $project->id,
            'name' => 'フリーライト ハンバーガー',
            'price' => 680,
            'category' => 'food',
            'is_available' => true,
            'stock' => 100,
            'smaregi_product_id' => '8000001',
        ]);

        \Illuminate\Support\Facades\Http::fake(function ($request) {
            $url = $request->url();
            $method = strtoupper($request->method());

            if (str_contains($url, '/token')) {
                return \Illuminate\Support\Facades\Http::response(['access_token' => 'token', 'expires_in' => 3600], 200);
            }
            if ($method === 'GET' && str_contains($url, 'pos/products')) {
                return \Illuminate\Support\Facades\Http::response([
                    'products' => [
                        ['productId' => '8000001', 'productName' => 'フリーライト ハンバーガー', 'price' => '680', 'displayFlag' => '1'],
                        ['productId' => '8000002', 'productName' => 'フリーライト ハンバーガー', 'price' => '680', 'displayFlag' => '1'],
                    ],
                ], 200);
            }
            if ($method === 'DELETE' && str_contains($url, 'pos/products/8000002')) {
                return \Illuminate\Support\Facades\Http::response([], 204);
            }

            return \Illuminate\Support\Facades\Http::response([], 404);
        });

        $result = app(SmaregiProductSyncService::class)->deduplicateSmaregiProducts((int) $project->id, 'test_contract_sb');

        $this->assertSame(1, $result['deleted']);
        $this->assertSame(0, $result['failed']);
    }
}
