<?php

namespace Tests\Unit\Domain\Coupon;

use App\Domain\Coupon\Policies\CouponIssuePolicy;
use Carbon\Carbon;
use Tests\TestCase;

class CouponIssuePolicyTest extends TestCase
{
    public function test_can_issue_once_未発行かつ期間内なら許可すること(): void
    {
        $this->assertTrue((new CouponIssuePolicy)->canIssueOnce(
            false,
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31'),
            Carbon::parse('2026-07-10'),
        ));
    }

    public function test_can_issue_once_既発行は拒否すること(): void
    {
        $this->assertFalse((new CouponIssuePolicy)->canIssueOnce(
            true,
            null,
            null,
            Carbon::parse('2026-07-10'),
        ));
    }

    public function test_can_issue_birthday_this_year_当年発行済みは拒否すること(): void
    {
        $this->assertFalse((new CouponIssuePolicy)->canIssueBirthdayThisYear(
            true,
            null,
            null,
            Carbon::parse('2026-07-10'),
        ));
    }

    public function test_resolve_validity_window_開始未設定なら発行日時を使うこと(): void
    {
        $now = Carbon::parse('2026-07-10 12:00:00');
        [$from, $until] = (new CouponIssuePolicy)->resolveValidityWindow(null, Carbon::parse('2026-12-31'), $now);

        $this->assertSame($now->getTimestamp(), $from->getTimestamp());
        $this->assertSame(Carbon::parse('2026-12-31')->getTimestamp(), $until->getTimestamp());
    }
}
