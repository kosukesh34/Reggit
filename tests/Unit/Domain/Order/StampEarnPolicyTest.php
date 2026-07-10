<?php

namespace Tests\Unit\Domain\Order;

use App\Domain\Order\Policies\StampEarnPolicy;
use Tests\TestCase;

class StampEarnPolicyTest extends TestCase
{
    public function test_calculate_count_金額と単価から枚数を計算すること(): void
    {
        $policy = new StampEarnPolicy;

        $this->assertSame(3, $policy->calculateCount(1500.0, 500));
        $this->assertSame(0, $policy->calculateCount(0.0, 500));
        $this->assertSame(0, $policy->calculateCount(1000.0, 0));
    }
}
