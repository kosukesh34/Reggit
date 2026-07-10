<?php

namespace Tests\Unit\Domain\Coupon;

use App\Domain\Coupon\Aggregates\MemberCoupon;
use App\Domain\Coupon\ValueObjects\MemberCouponId;
use Carbon\Carbon;
use Tests\TestCase;

class MemberCouponAggregateTest extends TestCase
{
    private function createUnused(
        ?\DateTimeInterface $validFrom = null,
        ?\DateTimeInterface $validUntil = null,
    ): MemberCoupon {
        return new MemberCoupon(
            new MemberCouponId(1),
            1,
            10,
            100,
            MemberCoupon::STATUS_UNUSED,
            $validFrom,
            $validUntil,
            null,
            'テストクーポン',
            null,
            'CODE1',
            'barcode',
        );
    }

    public function test_markAsUsed_未使用クーポンを使用済みにできること(): void
    {
        $coupon = $this->createUnused();
        $now = Carbon::parse('2026-07-10 12:00:00');

        $coupon->markAsUsed($now);

        $this->assertSame(MemberCoupon::STATUS_USED, $coupon->status());
        $this->assertTrue($coupon->wasMarkedUsed());
        $this->assertSame($now->getTimestamp(), $coupon->usedAt()?->getTimestamp());
    }

    public function test_markAsUsed_使用済みは拒否されること(): void
    {
        $coupon = $this->createUnused();
        $coupon->markAsUsed(Carbon::now());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('このクーポンは使用済みです');

        $coupon->markAsUsed(Carbon::now());
    }

    public function test_markAsUsed_利用開始前は拒否されること(): void
    {
        $coupon = $this->createUnused(Carbon::parse('2026-08-01'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('このクーポンはまだ利用開始前です');

        $coupon->markAsUsed(Carbon::parse('2026-07-10'));
    }

    public function test_markAsUsed_期限切れは拒否されること(): void
    {
        $coupon = $this->createUnused(null, Carbon::parse('2026-07-01'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('このクーポンは期限切れです');

        $coupon->markAsUsed(Carbon::parse('2026-07-10'));
    }
}
