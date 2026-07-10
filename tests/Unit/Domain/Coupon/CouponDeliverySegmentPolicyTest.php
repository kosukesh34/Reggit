<?php

namespace Tests\Unit\Domain\Coupon;

use App\Domain\Coupon\Policies\CouponDeliverySegmentPolicy;
use App\Enums\CouponDeliveryType;
use PHPUnit\Framework\TestCase;

class CouponDeliverySegmentPolicyTest extends TestCase
{
    public function test_normalize_birthday_month_範囲外はフォールバックすること(): void
    {
        $policy = new CouponDeliverySegmentPolicy;

        $this->assertSame(7, $policy->normalizeBirthdayMonth(0, 7));
        $this->assertSame(7, $policy->normalizeBirthdayMonth(13, 7));
        $this->assertSame(3, $policy->normalizeBirthdayMonth(3, 7));
    }

    public function test_build_segment_criteria_売上条件を組み立てること(): void
    {
        $criteria = (new CouponDeliverySegmentPolicy)->buildSegmentCriteria(
            CouponDeliveryType::IMMEDIATE_SEGMENT_SALES,
            [
                'segment_period_days' => 14,
                'segment_condition' => 'min_amount',
                'segment_min_amount' => 1000,
            ],
            5,
        );

        $this->assertSame([
            'period_days' => 14,
            'condition' => 'min_amount',
            'min_total_amount' => 1000.0,
        ], $criteria);
    }

    public function test_requires_personal_members_個人指定のみtrueであること(): void
    {
        $policy = new CouponDeliverySegmentPolicy;

        $this->assertTrue($policy->requiresPersonalMembers(CouponDeliveryType::IMMEDIATE_PERSONAL));
        $this->assertFalse($policy->requiresPersonalMembers(CouponDeliveryType::IMMEDIATE_ALL));
    }
}
