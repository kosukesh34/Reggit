<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Project;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiProductOptionSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SmaregiProductOptionSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_smaregi_option_products_become_mobile_order_choices(): void
    {
        $project = Project::factory()->create();
        $product = Product::factory()->create([
            'project_id' => $project->id,
            'name' => 'ラーメン',
            'smaregi_product_id' => '100',
        ]);
        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('request')->once()
            ->with('contract', 'GET', 'pos/product_option_groups/10', ['with_products' => 'all'])
            ->andReturn([
                'productOptionGroupId' => '10',
                'productOptionGroupName' => '麺の量',
                'min' => '0',
                'max' => '1',
                'products' => [['productId' => '101']],
            ]);

        $result = (new SmaregiProductOptionSyncService($api))->sync($project->id, 'contract', [
            ['productId' => '100', 'productName' => 'ラーメン', 'division' => '0', 'productOptionGroupId' => '10'],
            ['productId' => '101', 'productName' => '大盛り', 'division' => '2', 'price' => '150'],
        ]);

        $this->assertSame(1, $result['updated']);
        $group = $product->optionGroups()->with('options')->firstOrFail();
        $this->assertSame('麺の量', $group->name);
        $this->assertSame('single', $group->selection_type);
        $this->assertSame('大盛り', $group->options[0]->name);
        $this->assertSame('150.00', $group->options[0]->price_delta);
        $this->assertSame('101', $group->options[0]->smaregi_product_id);
    }
}
