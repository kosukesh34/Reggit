<?php

namespace Tests\Unit;

use App\Models\Coupon;
use App\Models\Project;
use App\Services\Smaregi\SmaregiApiService;
use App\Services\Smaregi\SmaregiCouponImportSyncService;
use App\Services\Smaregi\SmaregiCouponSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SmaregiCouponImportSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_syncFromSmaregi_スマレジのクーポンをDBに取り込むこと(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => 'Test', 'is_active' => true]
        );

        $api = Mockery::mock(SmaregiApiService::class);
        $api->shouldReceive('getContractIdForProject')->andReturn('contract-1');
        $api->shouldReceive('getCoupons')->once()->with('contract-1', Mockery::on(fn ($q) => $q['limit'] === 100))->andReturn([
            'coupons' => [[
                'couponId' => 'sm-coupon-1',
                'serialNumber' => 'IMPORT10',
                'couponName' => '取り込み10%オフ',
                'awardType' => '2',
                'awardValue' => '10',
            ]],
        ]);

        $sync = Mockery::mock(SmaregiCouponSyncService::class);
        $service = new SmaregiCouponImportSyncService($api, $sync);

        $result = $service->syncFromSmaregi($project->id);

        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertDatabaseHas('coupons', [
            'project_id' => $project->id,
            'code' => 'IMPORT10',
            'smaregi_coupon_id' => 'sm-coupon-1',
            'type' => 'percent',
        ]);
    }

    public function test_pushUnlinkedToSmaregi_未連携クーポンをPOSへ登録すること(): void
    {
        $project = Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default', 'description' => 'Test', 'is_active' => true]
        );

        $coupon = Coupon::factory()->create([
            'project_id' => $project->id,
            'code' => 'PUSH100',
            'name' => 'Push Coupon',
            'type' => 'fixed',
            'smaregi_coupon_id' => null,
        ]);

        $api = Mockery::mock(SmaregiApiService::class);
        $sync = Mockery::mock(SmaregiCouponSyncService::class);
        $sync->shouldReceive('hasSmaregiContract')->with($project->id)->andReturn(true);
        $sync->shouldReceive('sync')->once()->with(Mockery::on(fn ($c) => $c->id === $coupon->id))->andReturnUsing(function (Coupon $c) {
            $c->smaregi_coupon_id = 'sm-new-1';
            $c->save();

            return true;
        });

        $service = new SmaregiCouponImportSyncService($api, $sync);
        $result = $service->pushUnlinkedToSmaregi($project->id);

        $this->assertSame(1, $result['pushed']);
        $this->assertSame(0, $result['failed']);
    }
}
