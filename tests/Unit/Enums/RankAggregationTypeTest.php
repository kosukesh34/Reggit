<?php

namespace Tests\Unit\Enums;

use App\Enums\RankAggregationType;
use PHPUnit\Framework\TestCase;

class RankAggregationTypeTest extends TestCase
{
    public function test_normalize_未知の値は累計にすること(): void
    {
        $this->assertSame(RankAggregationType::TOTAL, RankAggregationType::normalize(null));
        $this->assertSame(RankAggregationType::TOTAL, RankAggregationType::normalize(''));
        $this->assertSame(RankAggregationType::TOTAL, RankAggregationType::normalize('unknown'));
        $this->assertSame(RankAggregationType::PERIOD, RankAggregationType::normalize(RankAggregationType::PERIOD));
    }

    public function test_is_period_期間のみtrueを返すこと(): void
    {
        $this->assertTrue(RankAggregationType::isPeriod(RankAggregationType::PERIOD));
        $this->assertFalse(RankAggregationType::isPeriod(RankAggregationType::TOTAL));
        $this->assertFalse(RankAggregationType::isPeriod(null));
    }
}
