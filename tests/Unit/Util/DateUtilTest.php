<?php

namespace Tests\Unit\Util;

use App\Util\DateUtil;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DateUtilTest extends TestCase
{
    #[DataProvider('nullableValuesProvider')]
    public function test_nullableCarbonはnullと空文字でnullを返すこと(mixed $value): void
    {
        $this->assertNull(DateUtil::nullableCarbon($value));
    }

    /** @return array<string, array{0: mixed}> */
    public static function nullableValuesProvider(): array
    {
        return [
            'null' => [null],
            'empty string' => [''],
        ];
    }

    public function test_nullableCarbonは文字列をCarbonに変換すること(): void
    {
        $result = DateUtil::nullableCarbon('2026-06-09 12:00:00');

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertSame('2026-06-09 12:00:00', $result?->format('Y-m-d H:i:s'));
    }

    public function test_nullableCarbonはCarbonインスタンスをそのまま返すこと(): void
    {
        $carbon = Carbon::parse('2026-06-09 10:00:00');

        $this->assertSame($carbon, DateUtil::nullableCarbon($carbon));
    }
}
