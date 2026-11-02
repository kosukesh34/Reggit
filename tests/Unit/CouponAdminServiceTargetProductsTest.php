<?php

namespace Tests\Unit;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Project;
use App\Services\Admin\CouponAdminService;
use App\Services\Smaregi\SmaregiCouponSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class CouponAdminServiceTargetProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @return array<string, mixed> */
    private function form(Coupon $coupon, int $productId): array
    {
        return ['code' => $coupon->code, 'name' => '変更後', 'type' => 'fixed', 'value' => 100, 'apply_condition_division' => '1', 'target_product_ids' => [$productId]];
    }

    public function test_update_対象商品の同期に失敗したら連携済みクーポンを_po_sで元に戻し_d_bも変えないこと(): void
    {
        $project = Project::factory()->create();
        $old = Product::factory()->create(['project_id' => $project->id, 'smaregi_product_id' => 'sm-old']);
        $new = Product::factory()->create(['project_id' => $project->id, 'smaregi_product_id' => 'sm-new']);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'name' => '変更前', 'smaregi_coupon_id' => 'sm-1', 'apply_condition_division' => '1']);
        $coupon->targetProducts()->sync([$old->id]);

        $sync = Mockery::mock(SmaregiCouponSyncService::class);
        $sync->shouldReceive('hasSmaregiContract')->andReturn(true);
        $sync->shouldReceive('updateOnSmaregi')->once()->with($project->id, 'sm-1', Mockery::on(fn ($data) => $data['name'] === '変更後'))->andReturn(true);
        $sync->shouldReceive('syncTargetProducts')->once()->with($project->id, 'sm-1', ['sm-new'])->andReturn(false);
        $sync->shouldReceive('updateOnSmaregi')->once()->with($project->id, 'sm-1', Mockery::on(fn ($data) => $data['name'] === '変更前'))->andReturn(true);
        $sync->shouldReceive('syncTargetProducts')->once()->with($project->id, 'sm-1', ['sm-old'])->andReturn(true);

        try {
            (new CouponAdminService($sync))->update($coupon->id, $this->form($coupon, $new->id), $project->id);
            $this->fail('RuntimeException expected');
        } catch (RuntimeException) {
        }

        $this->assertSame('変更前', $coupon->fresh()->name);
        $this->assertSame([$old->id], $coupon->fresh()->targetProducts->pluck('id')->all());
    }

    public function test_update_未連携クーポンの対象商品の同期に失敗したら今回登録した_po_sのクーポンを削除すること(): void
    {
        $project = Project::factory()->create();
        $product = Product::factory()->create(['project_id' => $project->id, 'smaregi_product_id' => 'sm-p']);
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'smaregi_coupon_id' => null]);

        $sync = Mockery::mock(SmaregiCouponSyncService::class);
        $sync->shouldReceive('hasSmaregiContract')->andReturn(true);
        $sync->shouldReceive('registerToSmaregi')->once()->andReturn(['smaregi_coupon_id' => 'sm-new']);
        $sync->shouldReceive('syncTargetProducts')->once()->andReturn(false);
        $sync->shouldReceive('deleteOnSmaregi')->once()->with($project->id, 'sm-new')->andReturn(true);

        $this->expectException(RuntimeException::class);
        try {
            (new CouponAdminService($sync))->update($coupon->id, $this->form($coupon, $product->id), $project->id);
        } finally {
            $this->assertNull($coupon->fresh()->smaregi_coupon_id);
        }
    }
}
