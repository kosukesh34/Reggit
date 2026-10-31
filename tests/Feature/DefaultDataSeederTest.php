<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Eloquent\Settings\ShopSettingStore;
use App\Models\Product;
use App\Models\Project;
use App\Services\Admin\ProductDeduplicationService;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiProductImageImporter;
use App\Services\Smaregi\SmaregiProductOptionSyncService;
use App\Services\Smaregi\SmaregiProductRegisterService;
use App\Services\Smaregi\SmaregiProductSyncService;
use Database\Seeders\DefaultDataSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Mockery;
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

        $this->assertSame(8, Product::withoutGlobalScopes()->where('project_id', $project->id)->count());
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

    public function test_smaregi_sync_スマレジの商品画像で古い画像を置き換えること(): void
    {
        Storage::fake('public');
        Http::fake(['https://pos-cache.smaregi.dev/*' => Http::response('fake-image', 200, ['Content-Type' => 'image/png'])]);
        $project = Project::factory()->create();
        Product::withoutGlobalScopes()->create([
            'project_id' => $project->id,
            'name' => '同名商品',
            'price' => 100,
            'category' => 'food',
            'is_available' => true,
            'smaregi_product_id' => 'NEW-ID',
            'image_url' => 'https://cdn.example.com/wrong.png',
        ]);

        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->with($project->id)->andReturn('contract-1');
        $api->shouldReceive('request')->with('contract-1', 'GET', 'pos/products', ['limit' => 100, 'page' => 1])
            ->andReturn(['products' => [
                ['productId' => 'NEW-ID', 'productName' => '同名商品', 'price' => '100', 'displayFlag' => '1'],
            ]]);
        $api->shouldReceive('request')->with('contract-1', 'GET', 'pos/products', ['limit' => 100, 'page' => 2])
            ->andReturn(['products' => []]);
        $api->shouldReceive('request')->with('contract-1', 'GET', 'pos/products/images', ['limit' => 100, 'page' => 1])
            ->andReturn([
                ['productId' => 'NEW-ID', 'url' => 'https://pos-cache.smaregi.dev/new.png'],
            ]);

        $optionSync = Mockery::mock(SmaregiProductOptionSyncService::class);
        $optionSync->shouldReceive('sync')->once()->andReturn(['updated' => 0, 'failed' => 0, 'errors' => []]);
        $deduplication = Mockery::mock(ProductDeduplicationService::class);
        $deduplication->shouldReceive('deduplicateForProject')->once()->with($project->id)->andReturn(0);

        $sync = new SmaregiProductSyncService(
            $api,
            Mockery::mock(SmaregiProductRegisterService::class),
            $deduplication,
            $optionSync,
            new SmaregiProductImageImporter($api),
        );

        $sync->sync((int) $project->id);

        $products = Product::withoutGlobalScopes()->where('project_id', $project->id)->get()->keyBy('smaregi_product_id');
        $this->assertCount(1, $products);
        $this->assertStringContainsString('/storage/product_images/'.$project->id.'/smaregi_', $products['NEW-ID']->image_url);
        $this->assertNotSame('https://pos-cache.smaregi.dev/new.png', $products['NEW-ID']->image_url);
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
        ShopSettingStore::setValue('smaregi_contract_id', 'test_contract_sb', 'string', (int) $project->id);
        Product::withoutGlobalScopes()->create([
            'project_id' => $project->id,
            'name' => 'フリーライト ハンバーガー',
            'price' => 680,
            'category' => 'food',
            'is_available' => true,
            'stock' => 100,
            'smaregi_product_id' => '8000001',
        ]);

        Http::fake(function ($request) {
            $url = $request->url();
            $method = strtoupper($request->method());

            if (str_contains($url, '/token')) {
                return Http::response(['access_token' => 'token', 'expires_in' => 3600], 200);
            }
            if ($method === 'GET' && str_contains($url, 'pos/products')) {
                return Http::response([
                    'products' => [
                        ['productId' => '8000001', 'productName' => 'フリーライト ハンバーガー', 'price' => '680', 'displayFlag' => '1'],
                        ['productId' => '8000002', 'productName' => 'フリーライト ハンバーガー', 'price' => '680', 'displayFlag' => '1'],
                    ],
                ], 200);
            }
            if ($method === 'DELETE' && str_contains($url, 'pos/products/8000002')) {
                return Http::response([], 204);
            }

            return Http::response([], 404);
        });

        $result = app(SmaregiProductSyncService::class)->deduplicateSmaregiProducts((int) $project->id, 'test_contract_sb');

        $this->assertSame(1, $result['deleted']);
        $this->assertSame(0, $result['failed']);
    }
}
