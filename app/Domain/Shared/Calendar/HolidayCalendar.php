<?php

namespace App\Domain\Shared\Calendar;

/** 日本の祝日（振替休日・国民の休日を含む） */
interface HolidayCalendar
{
    public function isHoliday(\DateTimeInterface $date): bool;
}
