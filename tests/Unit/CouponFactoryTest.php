<?php

namespace Tests\Unit;

use App\Models\Coupon;
use Tests\TestCase;

class CouponFactoryTest extends TestCase
{
    public function test_generated_codes_remain_unique_beyond_one_thousand_coupons(): void
    {
        $codes = Coupon::factory()->count(1001)->make()->pluck('code')->all();

        $this->assertCount(1001, array_unique($codes));
    }
}
