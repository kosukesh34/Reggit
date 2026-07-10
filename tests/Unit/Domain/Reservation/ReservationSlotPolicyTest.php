<?php

namespace Tests\Unit\Domain\Reservation;

use App\Domain\Reservation\Policies\ReservationSlotPolicy;
use Carbon\Carbon;
use Tests\TestCase;

class ReservationSlotPolicyTest extends TestCase
{
    public function test_availabilityError_期間外は拒否されること(): void
    {
        $error = (new ReservationSlotPolicy)->availabilityError(
            Carbon::parse('2026-07-10 12:00:00'),
            Carbon::parse('2026-07-10 10:00:00'),
            7,
            ['12:00'],
            true,
            5,
            0,
        );

        $this->assertSame('予約可能な期間外です', $error);
    }

    public function test_availabilityError_満席は拒否されること(): void
    {
        $error = (new ReservationSlotPolicy)->availabilityError(
            Carbon::parse('2026-07-11 12:00:00'),
            Carbon::parse('2026-07-10 10:00:00'),
            7,
            ['12:00'],
            true,
            2,
            2,
        );

        $this->assertSame('選択した時間帯は満席です', $error);
    }

    public function test_buildAvailableDates_空き枠のみ返すこと(): void
    {
        $dates = (new ReservationSlotPolicy)->buildAvailableDates(
            Carbon::parse('2026-07-10'),
            2,
            ['12:00', '18:00'],
            [
                '2026-07-11' => true,
                '2026-07-12' => false,
            ],
            [
                '2026-07-11' => ['12:00' => 1, '18:00' => 5],
            ],
            5,
        );

        $this->assertCount(1, $dates);
        $this->assertSame('2026-07-11', $dates[0]['date']);
        $this->assertSame(['12:00'], $dates[0]['time_slots']);
    }
}
