<?php

namespace Tests\Unit\Infrastructure\Calendar;

use App\Infrastructure\Calendar\YasumiHolidayCalendar;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class YasumiHolidayCalendarTest extends TestCase
{
    public function test_日本の祝日と振替休日と国民の休日を判定すること(): void
    {
        $calendar = new YasumiHolidayCalendar;

        foreach (['2026-11-03' => true, '2026-05-06' => true, '2026-09-22' => true, '2026-11-02' => false, '2027-01-01' => true] as $date => $expected) {
            $this->assertSame($expected, $calendar->isHoliday(CarbonImmutable::parse($date.' 10:00')), $date);
        }
    }
}
