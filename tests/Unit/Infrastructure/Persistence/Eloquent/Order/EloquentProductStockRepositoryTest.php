<?php

namespace Tests\Unit\Infrastructure\Persistence\Eloquent\Order;

use App\Infrastructure\Persistence\Eloquent\Order\EloquentProductStockRepository;
use App\Models\Product;
use App\Models\Project;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentProductStockRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_decrease_在庫管理対象の商品を減算すること(): void
    {
        $project = Project::factory()->create();
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'stock' => 10,
        ]);

        $repository = new EloquentProductStockRepository;
        $repository->decrease((int) $product->id, (int) $project->id, 3);

        $this->assertSame(7, (int) $product->fresh()->stock);
    }

    public function test_decrease_他プロジェクトの商品は変更しないこと(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'stock' => 10,
        ]);

        $repository = new EloquentProductStockRepository;
        try {
            $repository->decrease((int) $product->id, (int) $other->id, 3);
            $this->fail('A foreign project must be rejected');
        } catch (ModelNotFoundException $e) {
            $this->assertSame(10, (int) $product->fresh()->stock);
        }

        $this->assertSame(10, (int) $product->fresh()->stock);
    }

    public function test_decrease_在庫未管理の商品は変更しないこと(): void
    {
        $project = Project::factory()->create();
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'stock' => null,
        ]);

        $repository = new EloquentProductStockRepository;
        $repository->decrease((int) $product->id, (int) $project->id, 3);

        $this->assertNull($product->fresh()->stock);
    }

    public function test_restore_在庫を戻すこと(): void
    {
        $project = Project::factory()->create();
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'stock' => 5,
        ]);

        $repository = new EloquentProductStockRepository;
        $repository->restore((int) $product->id, (int) $project->id, 2);

        $this->assertSame(7, (int) $product->fresh()->stock);
    }
}
