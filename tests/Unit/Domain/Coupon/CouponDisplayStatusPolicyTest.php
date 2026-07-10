<?php

namespace Tests\Unit\Domain\Coupon;

use App\Domain\Coupon\Policies\CouponDisplayStatusPolicy;
use Carbon\Carbon;
use Tests\TestCase;

class CouponDisplayStatusPolicyTest extends TestCase
{
    public function test_resolve_表示ステータスを判定できること(): void
    {
        $now = Carbon::parse('2026-07-10');
        $policy = new CouponDisplayStatusPolicy;

        $this->assertSame('used', $policy->resolve('used', null, null, $now));
        $this->assertSame('upcoming', $policy->resolve(
            'unused',
            Carbon::parse('2026-08-01'),
            null,
            $now,
        ));
        $this->assertSame('expired', $policy->resolve(
            'unused',
            null,
            Carbon::parse('2026-07-01'),
            $now,
        ));
        $this->assertSame('available', $policy->resolve('unused', null, null, $now));
    }
}
