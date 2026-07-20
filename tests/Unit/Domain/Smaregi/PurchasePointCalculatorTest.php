<?php

namespace Tests\Unit\Domain\Smaregi;

use App\Domain\Smaregi\Services\PurchasePointCalculator;
use PHPUnit\Framework\TestCase;

class PurchasePointCalculatorTest extends TestCase
{
    public function test_金額とレートからポイントを算出すること(): void
    {
        $calculator = new PurchasePointCalculator;

        $this->assertSame(20, $calculator->calculate(2046, 100));
        $this->assertSame(0, $calculator->calculate(0, 100));
        $this->assertSame(0, $calculator->calculate(1000, 0));
        $this->assertSame(0, $calculator->calculate(-100, 100));
    }
}
