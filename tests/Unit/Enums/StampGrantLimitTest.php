<?php

namespace Tests\Unit\Enums;

use App\Enums\StampGrantLimit;
use PHPUnit\Framework\TestCase;

class StampGrantLimitTest extends TestCase
{
    public function test_未知の上限は会員1人1回に正規化すること(): void
    {
        $this->assertSame(StampGrantLimit::PER_MEMBER, StampGrantLimit::normalize(''));
        $this->assertSame(StampGrantLimit::PER_MEMBER, StampGrantLimit::normalize('invalid'));
        $this->assertSame(StampGrantLimit::ONCE, StampGrantLimit::normalize(StampGrantLimit::ONCE));
        $this->assertSame(StampGrantLimit::UNLIMITED, StampGrantLimit::normalize(StampGrantLimit::UNLIMITED));
    }

    public function test_付与個数は1から上限の範囲に収めること(): void
    {
        $this->assertSame(1, StampGrantLimit::normalizeAmount(0));
        $this->assertSame(3, StampGrantLimit::normalizeAmount(3));
        $this->assertSame(StampGrantLimit::MAX_AMOUNT, StampGrantLimit::normalizeAmount(99));
    }

    public function test_有効期限は選択肢にない値をデフォルトに戻すこと(): void
    {
        $this->assertSame(60, StampGrantLimit::normalizeExpiresMinutes(60));
        $this->assertSame(StampGrantLimit::NEVER_EXPIRES, StampGrantLimit::normalizeExpiresMinutes(0));
        $this->assertSame(525600, StampGrantLimit::normalizeExpiresMinutes(525600));
        $this->assertSame(StampGrantLimit::DEFAULT_EXPIRES_MINUTES, StampGrantLimit::normalizeExpiresMinutes(7));
    }
}
