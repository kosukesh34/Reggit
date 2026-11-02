<?php

namespace Tests\Unit;

use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCouponSyncService;
use Mockery;
use Tests\TestCase;

class SmaregiCouponSyncServiceTargetProductsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sync_target_products_商品_i_d指定の対象商品だけを差分で登録_削除すること(): void
    {
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

        $ok = (new SmaregiCouponSyncService($api))->syncTargetProducts(1, 'sm-coupon-1', ['keep', 'add']);

        $this->assertTrue($ok);
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
}
