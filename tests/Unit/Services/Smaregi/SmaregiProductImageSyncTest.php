<?php

namespace Tests\Unit\Services\Smaregi;

use App\Models\Product;
use App\Models\Project;
use App\Services\Admin\ProductDeduplicationService;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiProductImageImporter;
use App\Services\Smaregi\SmaregiProductOptionSyncService;
use App\Services\Smaregi\SmaregiProductRegisterService;
use App\Services\Smaregi\SmaregiProductSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class SmaregiProductImageSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_image_cleanup_keeps_other_projects_files_and_rejects_unmanaged_paths(): void
    {
        Storage::fake('public');
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $disk = Storage::disk('public');
        $own = 'product_images/'.$project->id.'/own.png';
        $victim = 'product_images/'.$other->id.'/dummy.png';
        $foreign = 'product_images/'.$project->id.'/foreign.png';
        $disk->put($own, 'own');
        $disk->put($victim, 'dummy');
        $disk->put($foreign, 'foreign');
        $urls = [
            asset('storage/product_images/'.$project->id.'/../'.$other->id.'/dummy.png'),
            asset('storage/product_images/'.$project->id.'/..\\'.$other->id.'\\dummy.png'),
            asset('storage/product_images/'.$project->id.'/%2e%2e/'.$other->id.'/dummy.png'),
            'https://foreign.example/storage/'.$foreign,
            asset('storage/'.$own),
        ];
        $rows = [];
        foreach ($urls as $index => $url) {
            Product::factory()->create(['project_id' => $project->id, 'smaregi_product_id' => (string) ($index + 1), 'image_url' => $url]);
            $rows[] = ['productId' => (string) ($index + 1), 'productName' => 'Dummy '.$index, 'price' => 100];
        }
        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('dummy');
        $api->shouldReceive('request')->with('dummy', 'GET', 'pos/products', Mockery::any())->andReturn($rows);
        $api->shouldReceive('request')->with('dummy', 'GET', 'pos/products/images', Mockery::any())->andReturn([]);
        $options = Mockery::mock(SmaregiProductOptionSyncService::class);
        $options->shouldReceive('sync')->andReturn(['failed' => 0, 'errors' => []]);
        $sync = new SmaregiProductSyncService($api, Mockery::mock(SmaregiProductRegisterService::class),
            app(ProductDeduplicationService::class), $options, new SmaregiProductImageImporter($api));
        $sync->sync($project->id);
        $disk->assertExists($victim);
        $disk->assertExists($foreign);
        $disk->assertMissing($own);
        $this->assertSame(0, Product::where('project_id', $project->id)->where('image_url', '!=', '')->count());
    }

    public function test_sync_all_images_画像とsmaregi_idがある商品だけ送ること(): void
    {
        $project = Project::factory()->create();
        Product::factory()->create([
            'project_id' => $project->id,
            'name' => '画像あり',
            'smaregi_product_id' => '11',
            'image_url' => 'https://cdn.example.com/a.png',
        ]);
        Product::factory()->create([
            'project_id' => $project->id,
            'name' => '画像なし',
            'smaregi_product_id' => '22',
            'image_url' => '',
        ]);
        Product::factory()->create([
            'project_id' => $project->id,
            'name' => '未連携',
            'smaregi_product_id' => null,
            'image_url' => 'https://cdn.example.com/b.png',
        ]);

        $register = Mockery::mock(SmaregiProductRegisterService::class);
        $register->shouldReceive('hasSmaregiContract')->once()->with($project->id)->andReturn(true);
        $register->shouldReceive('registerProductImage')
            ->once()
            ->with($project->id, '11', 'https://cdn.example.com/a.png')
            ->andReturn(true);

        $sync = new SmaregiProductSyncService(
            Mockery::mock(SmaregiApiService::class),
            $register,
            Mockery::mock(ProductDeduplicationService::class),
            Mockery::mock(SmaregiProductOptionSyncService::class),
            Mockery::mock(SmaregiProductImageImporter::class),
        );

        $result = $sync->syncAllImagesToSmaregi((int) $project->id);

        $this->assertSame(1, $result['synced']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame([], $result['errors']);
    }
}
