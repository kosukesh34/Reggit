<?php

namespace Tests\Unit;

use App\Enums\CouponDeliveryStatus;
use App\Enums\CouponDeliveryType;
use App\Models\Coupon;
use App\Models\CouponDelivery;
use App\Models\Member;
use App\Models\Project;
use App\Services\Admin\CouponDeliveryTargetService;
use App\UseCases\Admin\Coupon\AdminCouponBulkIssueUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CouponDeliveryTargetServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createProject(): Project
    {
        return Project::firstOrCreate(
            ['project_key' => 'default'],
            ['name' => 'Default Project', 'description' => 'Test', 'is_active' => true]
        );
    }

    public function test_build_resolve_params_from_delivery_売上セグメントの保存条件を復元すること(): void
    {
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        $delivery = CouponDelivery::create([
            'project_id' => $project->id,
            'coupon_id' => $coupon->id,
            'delivery_type' => CouponDeliveryType::SCHEDULED_SEGMENT_SALES,
            'status' => CouponDeliveryStatus::SCHEDULED,
            'scheduled_at' => now()->addDay(),
            'segment_criteria' => [
                'period_days' => 60,
                'condition' => 'no_orders',
                'min_total_amount' => null,
            ],
        ]);

        $params = app(CouponDeliveryTargetService::class)->buildResolveParamsFromDelivery($delivery);

        $this->assertSame(60, $params['segment_period_days']);
        $this->assertSame('no_orders', $params['segment_condition']);
        $this->assertArrayNotHasKey('segment_min_amount', $params);
    }

    public function test_handle_絞り込み即時配布で対象0人の場合は例外になること(): void
    {
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('配布対象の会員がいません');

        app(AdminCouponBulkIssueUseCase::class)->handle($project->id, $coupon->id, [
            'delivery_type' => CouponDeliveryType::IMMEDIATE_BIRTHDAY,
            'birthday_month' => 2,
        ]);
    }

    public function test_handle_全員即時配布では空配列でも配布処理を呼べること(): void
    {
        $project = $this->createProject();
        $coupon = Coupon::factory()->create(['project_id' => $project->id]);
        Member::factory()->create(['project_id' => $project->id]);

        $result = app(AdminCouponBulkIssueUseCase::class)->handle($project->id, $coupon->id, [
            'delivery_type' => CouponDeliveryType::IMMEDIATE_ALL,
        ]);

        $this->assertSame(1, $result['issued']);
        $this->assertFalse($result['scheduled']);
    }
}
