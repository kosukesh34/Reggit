<?php

namespace Tests\Unit\Services\Admin;

use App\Models\Product;
use App\Models\Project;
use App\Services\Admin\ProductAdminService;
use App\Services\Smaregi\SmaregiProductRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProductAdminServiceTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->project = Project::factory()->create();
    }

    public function test_create_画像付きでスマレジ登録後に画像も送ること(): void
    {
        $register = Mockery::mock(SmaregiProductRegisterService::class);
        $register->shouldReceive('hasSmaregiContract')->once()->with($this->project->id)->andReturn(true);
        $register->shouldReceive('registerToSmaregi')->once()->andReturn(['smaregi_product_id' => '99']);
        $register->shouldReceive('syncStockToSmaregi')->once()->with($this->project->id, '99', 10);
        $register->shouldReceive('registerProductImage')
            ->once()
            ->with($this->project->id, '99', 'https://cdn.example.com/latte.png');

        $service = new ProductAdminService($register);
        $product = $service->create($this->project->id, [
            'name' => 'カフェラテ',
            'price' => 560,
            'category' => 'drink',
            'stock' => 10,
            'is_available' => true,
            'image_url' => 'https://cdn.example.com/latte.png',
        ]);

        $this->assertSame('99', $product->smaregi_product_id);
        $this->assertSame('https://cdn.example.com/latte.png', $product->image_url);
    }

    public function test_update_画像クリア時はスマレジの画像を削除すること(): void
    {
        $product = Product::factory()->create([
            'project_id' => $this->project->id,
            'smaregi_product_id' => '88',
            'image_url' => 'https://cdn.example.com/old.png',
            'name' => 'クリア対象',
            'price' => 300,
            'stock' => 5,
        ]);

        $register = Mockery::mock(SmaregiProductRegisterService::class);
        $register->shouldReceive('hasSmaregiContract')->once()->with($this->project->id)->andReturn(true);
        $register->shouldReceive('updateOnSmaregi')->once()->andReturn(true);
        $register->shouldReceive('syncStockToSmaregi')->once()->with($this->project->id, '88', 5);
        $register->shouldReceive('deleteProductImage')->once()->with($this->project->id, '88');
        $register->shouldReceive('registerProductImage')->never();

        $service = new ProductAdminService($register);
        $updated = $service->update($product->id, [
            'name' => 'クリア対象',
            'price' => 300,
            'stock' => 5,
            'is_available' => true,
            'image_url' => '',
        ]);

        $this->assertSame('', (string) $updated->image_url);
    }

    public function test_create_画像なしなら画像登録を呼ばないこと(): void
    {
        $register = Mockery::mock(SmaregiProductRegisterService::class);
        $register->shouldReceive('hasSmaregiContract')->andReturn(true);
        $register->shouldReceive('registerToSmaregi')->once()->andReturn(['smaregi_product_id' => '77']);
        $register->shouldReceive('registerProductImage')->never();
        $register->shouldReceive('deleteProductImage')->never();
        $register->shouldReceive('syncStockToSmaregi')->never();

        $service = new ProductAdminService($register);
        $service->create($this->project->id, [
            'name' => '画像なし',
            'price' => 100,
            'category' => 'other',
            'is_available' => true,
        ]);
    }
}
