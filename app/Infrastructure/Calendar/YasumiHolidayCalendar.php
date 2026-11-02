<?php

namespace App\Infrastructure\Calendar;

use App\Domain\Shared\Calendar\HolidayCalendar;
use Yasumi\Yasumi;

class YasumiHolidayCalendar implements HolidayCalendar
{
    /** @var array<int, list<string>> 年 => 祝日（Y-m-d） */
    private array $holidaysByYear = [];

    public function isHoliday(\DateTimeInterface $date): bool
    {
        $year = (int) $date->format('Y');
        $this->holidaysByYear[$year] ??= array_values(Yasumi::create('Japan', $year)->getHolidayDates());

        return in_array($date->format('Y-m-d'), $this->holidaysByYear[$year], true);
    }
}
