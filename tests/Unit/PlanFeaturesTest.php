<?php

namespace Tests\Unit;

use App\Enums\ProjectPlan;
use App\Support\PlanFeatures;
use PHPUnit\Framework\TestCase;

class PlanFeaturesTest extends TestCase
{
    public function test_normalプランは基本機能のみ(): void
    {
        $this->assertTrue(PlanFeatures::hasFeature(ProjectPlan::NORMAL, PlanFeatures::ORDERS));
        $this->assertFalse(PlanFeatures::hasFeature(ProjectPlan::NORMAL, PlanFeatures::LOTTERY));
        $this->assertFalse(PlanFeatures::hasFeature(ProjectPlan::NORMAL, PlanFeatures::ANNOUNCEMENT_DELIVERY));
    }

    public function test_distributionプランは配信機能を含む(): void
    {
        $this->assertTrue(PlanFeatures::hasFeature(ProjectPlan::DISTRIBUTION, PlanFeatures::COUPONS));
        $this->assertTrue(PlanFeatures::hasFeature(ProjectPlan::DISTRIBUTION, PlanFeatures::ANNOUNCEMENT_DELIVERY));
        $this->assertFalse(PlanFeatures::hasFeature(ProjectPlan::DISTRIBUTION, PlanFeatures::LOTTERY));
    }

    public function test_premiumプランは全機能を含む(): void
    {
        $this->assertTrue(PlanFeatures::hasFeature(ProjectPlan::PREMIUM, PlanFeatures::LOTTERY));
        $this->assertTrue(PlanFeatures::hasFeature(ProjectPlan::PREMIUM, PlanFeatures::ANNOUNCEMENT_DELIVERY));
    }
}
