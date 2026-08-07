<?php

namespace Tests\Unit\Util;

use App\Util\DateUtil;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class DateUtilTest extends TestCase
{
    public function test_nullや空文字はnullを返すこと(): void
    {
        $this->assertNull(DateUtil::nullableCarbon(null));
        $this->assertNull(DateUtil::nullableCarbon(''));
        $this->assertNull(DateUtil::nullableCarbon('   '));
    }

    public function test_不正な文字列はnullを返すこと(): void
    {
        $this->assertNull(DateUtil::nullableCarbon('not-a-date'));
    }

    public function test_パース可能な文字列は_carbonを返すこと(): void
    {
        $carbon = DateUtil::nullableCarbon('2026-07-19T01:44:25+09:00');

        $this->assertInstanceOf(Carbon::class, $carbon);
        $this->assertSame('2026-07-19 01:44:25', $carbon->format('Y-m-d H:i:s'));
    }

    public function test_carbonはそのまま返すこと(): void
    {
        $original = Carbon::parse('2026-08-06 12:00:00');

        $this->assertSame($original, DateUtil::nullableCarbon($original));
    }
}
