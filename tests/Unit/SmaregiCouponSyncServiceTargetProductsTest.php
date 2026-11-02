<?php

namespace Tests\Unit;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\Project;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCouponSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SmaregiCouponSyncServiceTargetProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sync_target_products_商品_i_d指定の対象商品だけを差分で登録_削除すること(): void
    {
        $project = Project::factory()->create();
        Product::factory()->create(['project_id' => $project->id, 'smaregi_product_id' => 'remove']);
        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $api->shouldReceive('getCoupon')->once()->with('contract-1', 'sm-coupon-1', ['with_items' => 'all'])->andReturn([
            'couponId' => 'sm-coupon-1',
            'items' => [
                ['type' => '1', 'division' => '2', 'itemId' => 'keep'],
                ['type' => '1', 'division' => '2', 'itemId' => 'remove'],
                ['type' => '1', 'division' => '1', 'itemId' => 'department'],
                ['type' => '2', 'division' => '2', 'itemId' => 'gift'],
            ],
        ]);
        $api->shouldReceive('createCouponProduct')->once()
            ->with('contract-1', 'sm-coupon-1', ['type' => '1', 'division' => '2', 'itemId' => 'add'])
            ->andReturn([]);
        $api->shouldReceive('deleteCouponProduct')->once()
            ->with('contract-1', 'sm-coupon-1', '1', '2', 'remove')
            ->andReturn([]);

        $ok = (new SmaregiCouponSyncService($api))->syncTargetProducts($project->id, 'sm-coupon-1', ['keep', 'add']);

        $this->assertTrue($ok);
    }

    public function test_sync_target_products_取り込んでいない商品は対象商品を指定する間は削除しないこと(): void
    {
        $project = Project::factory()->create();
        Product::factory()->create(['project_id' => $project->id, 'smaregi_product_id' => 'imported']);
        $api = $this->apiWithTargets(['imported', 'not-imported']);
        $api->shouldNotReceive('deleteCouponProduct');
        $api->shouldReceive('createCouponProduct')->once()
            ->with('contract-1', 'sm-coupon-1', ['type' => '1', 'division' => '2', 'itemId' => 'add'])
            ->andReturn([]);

        $this->assertTrue((new SmaregiCouponSyncService($api))->syncTargetProducts($project->id, 'sm-coupon-1', ['imported', 'add']));
    }

    public function test_sync_target_products_対象商品を空にすると取り込んでいない商品も外すこと(): void
    {
        $project = Project::factory()->create();
        $api = $this->apiWithTargets(['not-imported']);
        $api->shouldReceive('deleteCouponProduct')->once()
            ->with('contract-1', 'sm-coupon-1', '1', '2', 'not-imported')
            ->andReturn([]);

        $this->assertTrue((new SmaregiCouponSyncService($api))->syncTargetProducts($project->id, 'sm-coupon-1', []));
    }

    /** @param  list<string>  $itemIds  スマレジに登録済みの対象商品（商品ID指定） */
    private function apiWithTargets(array $itemIds): SmaregiApiService&Mockery\MockInterface
    {
        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $api->shouldReceive('getCoupon')->once()->with('contract-1', 'sm-coupon-1', ['with_items' => 'all'])->andReturn([
            'couponId' => 'sm-coupon-1',
            'items' => array_map(fn (string $itemId) => ['type' => '1', 'division' => '2', 'itemId' => $itemId], $itemIds),
        ]);

        return $api;
    }

    public function test_sync_target_products_取得に失敗したら変更せず_falseを返すこと(): void
    {
        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $api->shouldReceive('getCoupon')->once()->andReturn(null);
        $api->shouldNotReceive('createCouponProduct');
        $api->shouldNotReceive('deleteCouponProduct');

        $this->assertFalse((new SmaregiCouponSyncService($api))->syncTargetProducts(1, 'sm-coupon-1', ['add']));
    }

    public function test_sync_スマレジ未連携の対象商品があれば_po_sに登録せず止めること(): void
    {
        $project = Project::factory()->create();
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'smaregi_coupon_id' => null, 'apply_condition_division' => '1']);
        $coupon->targetProducts()->sync([Product::factory()->create(['project_id' => $project->id, 'name' => '未連携ラテ', 'smaregi_product_id' => null])->id]);
        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $api->shouldNotReceive('createCoupon');

        $this->expectExceptionMessage('スマレジ未連携の商品は対象商品にできません：未連携ラテ');
        (new SmaregiCouponSyncService($api))->sync($coupon);
    }

    public function test_sync_対象商品の登録に失敗したら_po_sのクーポンを削除し未連携のままにすること(): void
    {
        $project = Project::factory()->create();
        $coupon = Coupon::factory()->create(['project_id' => $project->id, 'smaregi_coupon_id' => null, 'apply_condition_division' => '1']);
        $coupon->targetProducts()->sync([Product::factory()->create(['project_id' => $project->id, 'smaregi_product_id' => 'sm-p-1'])->id]);
        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $api->shouldReceive('createCoupon')->once()->andReturn(['couponId' => 'sm-new']);
        $api->shouldReceive('getCoupon')->once()->andReturn(null);
        $api->shouldReceive('deleteCoupon')->once()->with('contract-1', 'sm-new')->andReturn([]);

        $this->assertFalse((new SmaregiCouponSyncService($api))->sync($coupon));
        $this->assertNull($coupon->fresh()->smaregi_coupon_id);
    }
}
