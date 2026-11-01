<?php

namespace App\Domain\Settings\ValueObjects;

use Carbon\CarbonImmutable;

/** 営業時間（H:i）。閉店が開店より前なら翌日の閉店、同じ時刻なら24時間営業として扱う */
final class BusinessHours
{
    public function __construct(
        public readonly string $start,
        public readonly string $end,
    ) {}

    /** @return array{0: CarbonImmutable, 1: CarbonImmutable} 営業日 $date の開店と閉店 */
    public function windowOn(CarbonImmutable $date): array
    {
        $open = $date->setTimeFromTimeString($this->start);
        $close = $date->setTimeFromTimeString($this->end);

        return [$open, $close <= $open ? $close->addDay() : $close];
    }

    /** $moment が属する営業日。日をまたぐ営業の閉店前の深夜は前日の営業日になる */
    public function businessDateAt(CarbonImmutable $moment): CarbonImmutable
    {
        $today = $moment->startOfDay();
        [, $previousClose] = $this->windowOn($today->subDay());

        return $moment < $previousClose ? $today->subDay() : $today;
    }
}
