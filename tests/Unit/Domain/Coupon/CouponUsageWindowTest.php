<?php

namespace Tests\Unit\Domain\Coupon;

use App\Domain\Coupon\ValueObjects\CouponUsageWindow;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class CouponUsageWindowTest extends TestCase
{
    private function weekdayMorning(): CouponUsageWindow
    {
        return CouponUsageWindow::fromArrays(['1', '2', '3', '4', '5'], [['start' => '10:00', 'end' => '12:00']]);
    }

    public function test_平日午前中は曜日と時間帯の両方に入るときだけ使えること(): void
    {
        $window = $this->weekdayMorning();

        $this->assertTrue($window->allows(CarbonImmutable::parse('2026-11-02 10:00'), false));
        $this->assertTrue($window->allows(CarbonImmutable::parse('2026-11-02 11:59'), false));
        $this->assertFalse($window->allows(CarbonImmutable::parse('2026-11-02 12:00'), false), '終了時刻は含まない');
        $this->assertFalse($window->allows(CarbonImmutable::parse('2026-11-02 09:59'), false));
        $this->assertFalse($window->allows(CarbonImmutable::parse('2026-11-07 10:30'), false), '土曜');
    }

    public function test_祝日は祝日を選んだときだけ使えること(): void
    {
        // 2026-11-03（火）は文化の日、2026-11-23（月）は勤労感謝の日
        $this->assertFalse($this->weekdayMorning()->allows(CarbonImmutable::parse('2026-11-03 10:30'), true));
        $this->assertTrue(CouponUsageWindow::fromArrays(['1', '2', '3', '4', '5', 'holiday'], [])->allows(CarbonImmutable::parse('2026-11-23 10:30'), true));
        $this->assertFalse(CouponUsageWindow::fromArrays(['0', '6'], [])->allows(CarbonImmutable::parse('2026-11-23 10:30'), true));
    }

    public function test_時間帯は複数持てて制限がなければいつでも使えること(): void
    {
        $lunchAndDinner = CouponUsageWindow::fromArrays(null, [['start' => '11:00', 'end' => '14:00'], ['start' => '17:00', 'end' => '20:00']]);

        $this->assertTrue($lunchAndDinner->allows(CarbonImmutable::parse('2026-11-08 18:00'), true));
        $this->assertFalse($lunchAndDinner->allows(CarbonImmutable::parse('2026-11-08 15:00'), false));
        $this->assertTrue(CouponUsageWindow::fromArrays(null, null)->allows(CarbonImmutable::parse('2026-11-03 03:00'), true));
        $this->assertFalse(CouponUsageWindow::fromArrays(null, null)->isRestricted());
    }

    public function test_使える日時を文章にすること(): void
    {
        $this->assertSame('平日（祝日を除く） 10:00〜12:00', $this->weekdayMorning()->label());
        $this->assertSame('土日・祝日', CouponUsageWindow::fromArrays(['6', '0', 'holiday'], null)->label());
        $this->assertSame('月・水（祝日を除く） 11:00〜14:00、17:00〜20:00', CouponUsageWindow::fromArrays(['3', '1'], [['start' => '11:00', 'end' => '14:00'], ['start' => '17:00', 'end' => '20:00']])->label());
        $this->assertSame('祝日', CouponUsageWindow::fromArrays(['holiday'], null)->label());
        $this->assertSame('17:00〜20:00', CouponUsageWindow::fromArrays(null, [['start' => '17:00', 'end' => '20:00']])->label());
        $this->assertNull(CouponUsageWindow::fromArrays([], [])->label());
    }
}
