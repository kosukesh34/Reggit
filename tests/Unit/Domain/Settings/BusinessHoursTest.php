<?php

namespace Tests\Unit\Domain\Settings;

use App\Domain\Settings\ValueObjects\BusinessHours;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BusinessHoursTest extends TestCase
{
    /** @return iterable<string, array{string, string, string, string}> */
    public static function windows(): iterable
    {
        yield '通常営業' => ['10:00', '22:00', '2026-11-02 10:00', '2026-11-02 22:00'];
        yield '日またぎは翌日の閉店' => ['18:00', '03:00', '2026-11-02 18:00', '2026-11-03 03:00'];
        yield '同じ時刻は24時間営業' => ['00:00', '00:00', '2026-11-02 00:00', '2026-11-03 00:00'];
    }

    #[DataProvider('windows')]
    public function test_window_on_営業日の開店と閉店を返すこと(string $start, string $end, string $open, string $close): void
    {
        [$actualOpen, $actualClose] = (new BusinessHours($start, $end))->windowOn(CarbonImmutable::parse('2026-11-02'));

        $this->assertSame([$open, $close], [$actualOpen->format('Y-m-d H:i'), $actualClose->format('Y-m-d H:i')]);
    }

    /** @return iterable<string, array{string, string, string, string}> */
    public static function businessDates(): iterable
    {
        yield '通常営業の深夜は当日' => ['10:00', '22:00', '2026-11-02 01:00', '2026-11-02'];
        yield '日またぎの閉店前は前日' => ['18:00', '03:00', '2026-11-02 02:59', '2026-11-01'];
        yield '日またぎの閉店後は当日' => ['18:00', '03:00', '2026-11-02 03:00', '2026-11-02'];
        yield '24時間営業は暦どおり' => ['00:00', '00:00', '2026-11-02 00:30', '2026-11-02'];
    }

    #[DataProvider('businessDates')]
    public function test_business_date_at_時刻が属する営業日を返すこと(string $start, string $end, string $moment, string $expected): void
    {
        $date = (new BusinessHours($start, $end))->businessDateAt(CarbonImmutable::parse($moment));

        $this->assertSame($expected.' 00:00', $date->format('Y-m-d H:i'));
    }
}
