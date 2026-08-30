<?php

namespace Tests\Unit\Services\Smaregi;

use App\Models\Product;
use App\Models\Project;
use App\Services\Admin\ProductDeduplicationService;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiProductRegisterService;
use App\Services\Smaregi\SmaregiProductSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SmaregiProductImageSyncTest extends TestCase
{
    use RefreshDatabase;

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
        );

        $result = $sync->syncAllImagesToSmaregi((int) $project->id);

        $this->assertSame(1, $result['synced']);
        $this->assertSame(0, $result['failed']);
        $this->assertSame([], $result['errors']);
    }
}
