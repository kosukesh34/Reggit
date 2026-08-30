<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Services\Smaregi\SmaregiProductSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SmaregiSyncProductImagesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_指定プロジェクトの画像をスマレジへ反映すること(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $sync = Mockery::mock(SmaregiProductSyncService::class);
        $sync->shouldReceive('syncAllImagesToSmaregi')
            ->once()
            ->with($project->id)
            ->andReturn(['synced' => 2, 'failed' => 0, 'errors' => []]);
        $this->app->instance(SmaregiProductSyncService::class, $sync);

        $this->artisan('smaregi:sync-product-images', ['--project_id' => (string) $project->id])
            ->assertSuccessful()
            ->expectsOutputToContain('画像反映: 成功=2, 失敗=0');
    }

    public function test_失敗があるときは非ゼロで終了すること(): void
    {
        $project = Project::factory()->create(['is_active' => true]);
        $sync = Mockery::mock(SmaregiProductSyncService::class);
        $sync->shouldReceive('syncAllImagesToSmaregi')
            ->once()
            ->andReturn(['synced' => 0, 'failed' => 1, 'errors' => ['画像同期失敗 product_id=1']]);
        $this->app->instance(SmaregiProductSyncService::class, $sync);

        $this->artisan('smaregi:sync-product-images', ['--project_id' => (string) $project->id])
            ->assertFailed();
    }
}
