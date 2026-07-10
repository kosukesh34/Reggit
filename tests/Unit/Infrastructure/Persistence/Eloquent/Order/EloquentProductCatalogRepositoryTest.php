<?php

namespace Tests\Unit\Infrastructure\Persistence\Eloquent\Order;

use App\Infrastructure\Persistence\Eloquent\Order\EloquentProductCatalogRepository;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionGroup;
use App\Models\Project;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentProductCatalogRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_findForOrder_商品とオプションをドメインVOで返すこと(): void
    {
        $project = Project::factory()->create();
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'name' => 'テスト商品',
            'price' => 500,
            'stock' => 8,
        ]);
        $group = ProductOptionGroup::create([
            'project_id' => $project->id,
            'product_id' => $product->id,
            'name' => 'サイズ',
            'selection_type' => ProductOptionGroup::TYPE_SINGLE,
            'is_required' => true,
            'min_select' => 1,
            'max_select' => null,
            'sort_order' => 0,
        ]);
        ProductOption::create([
            'project_id' => $project->id,
            'group_id' => $group->id,
            'name' => 'L',
            'price_delta' => 100,
            'sort_order' => 0,
        ]);

        $repository = new EloquentProductCatalogRepository;
        $found = $repository->findForOrder((int) $product->id, (int) $project->id);

        $this->assertNotNull($found);
        $this->assertSame('テスト商品', $found->name);
        $this->assertSame(500.0, $found->price);
        $this->assertSame(8, $found->stock);
        $this->assertCount(1, $found->optionGroups);
        $this->assertSame('サイズ', $found->optionGroups[0]->name);
        $this->assertCount(1, $found->optionGroups[0]->options);
        $this->assertSame(100.0, $found->optionGroups[0]->options[0]->priceDelta);
    }

    public function test_findForOrder_他プロジェクトの商品は返さないこと(): void
    {
        $project = Project::factory()->create();
        $other = Project::factory()->create();
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'name' => '他店商品',
            'price' => 500,
        ]);

        $repository = new EloquentProductCatalogRepository;
        $found = $repository->findForOrder((int) $product->id, (int) $other->id);

        $this->assertNull($found);
    }

    public function test_findForOrderOrFail_存在しない商品は例外になること(): void
    {
        $project = Project::factory()->create();
        $repository = new EloquentProductCatalogRepository;

        $this->expectException(ModelNotFoundException::class);
        $repository->findForOrderOrFail(999999, (int) $project->id);
    }
}
