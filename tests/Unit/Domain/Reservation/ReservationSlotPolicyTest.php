<?php

namespace Tests\Unit\Domain\Reservation;

use App\Domain\Reservation\Policies\ReservationSlotPolicy;
use App\Domain\Reservation\ValueObjects\ReservationRules;
use Carbon\Carbon;
use Tests\TestCase;

class ReservationSlotPolicyTest extends TestCase
{
    /** @param list<string> $timeSlots */
    private function rules(
        array $timeSlots = ['12:00'],
        int $advanceDays = 7,
        int $capacity = 5,
        bool $capacityByPeople = false,
        int $maxPartySize = 10,
        bool $sameDay = false,
        int $leadMinutes = 0,
        int $changeDeadlineHours = 0,
    ): ReservationRules {
        return new ReservationRules($timeSlots, $advanceDays, $capacity, $capacityByPeople, $maxPartySize, $sameDay, $leadMinutes, $changeDeadlineHours);
    }

    private function error(string $reservedAt, string $now, ReservationRules $rules, int $people = 1, int $used = 0): ?string
    {
        return (new ReservationSlotPolicy)->availabilityError(Carbon::parse($reservedAt), Carbon::parse($now), $rules, true, $people, $used);
    }

    public function test_availability_error_期間外は拒否されること(): void
    {
        $this->assertSame('予約可能な期間外です', $this->error('2026-07-10 12:00:00', '2026-07-10 10:00:00', $this->rules()));
    }

    public function test_availability_error_満席は拒否されること(): void
    {
        $this->assertSame('選択した時間帯は満席です', $this->error('2026-07-11 12:00:00', '2026-07-10 10:00:00', $this->rules(capacity: 2), used: 2));
    }

    public function test_availability_error_人数で数えるときは入りきらない人数を拒否すること(): void
    {
        $rules = $this->rules(capacity: 6, capacityByPeople: true);

        $this->assertNull($this->error('2026-07-11 12:00:00', '2026-07-10 10:00:00', $rules, people: 2, used: 4));
        $this->assertSame('選択した時間帯は満席です', $this->error('2026-07-11 12:00:00', '2026-07-10 10:00:00', $rules, people: 3, used: 4));
    }

    public function test_availability_error_最大人数を超える予約は拒否すること(): void
    {
        $this->assertSame('1回の予約は4名までです', $this->error('2026-07-11 12:00:00', '2026-07-10 10:00:00', $this->rules(maxPartySize: 4), people: 5));
    }

    public function test_availability_error_当日予約と受付締め切り(): void
    {
        $sameDay = $this->rules(sameDay: true, leadMinutes: 60);

        $this->assertNull($this->error('2026-07-10 12:00:00', '2026-07-10 10:59:00', $sameDay));
        $this->assertSame('この時間の予約受付は終了しました', $this->error('2026-07-10 12:00:00', '2026-07-10 11:01:00', $sameDay));
        $this->assertSame('予約可能な期間外です', $this->error('2026-07-10 12:00:00', '2026-07-10 10:00:00', $this->rules()));
    }

    public function test_build_available_dates_空き枠のみ返すこと(): void
    {
        $dates = (new ReservationSlotPolicy)->buildAvailableDates(
            Carbon::parse('2026-07-10'),
            $this->rules(['12:00', '18:00'], advanceDays: 2),
            [
                '2026-07-11' => true,
                '2026-07-12' => false,
            ],
            [
                '2026-07-11' => ['12:00' => 1, '18:00' => 5],
            ],
            1,
        );

        $this->assertCount(1, $dates);
        $this->assertSame('2026-07-11', $dates[0]['date']);
        $this->assertSame(['12:00'], $dates[0]['time_slots']);
    }

    public function test_build_available_dates_当日は締め切り前の枠と人数が入る枠だけを返すこと(): void
    {
        $dates = (new ReservationSlotPolicy)->buildAvailableDates(
            Carbon::parse('2026-07-10 11:30:00'),
            $this->rules(['12:00', '13:00', '18:00'], advanceDays: 0, capacity: 6, capacityByPeople: true, sameDay: true, leadMinutes: 60),
            ['2026-07-10' => true],
            ['2026-07-10' => ['18:00' => 4]],
            3,
        );

        $this->assertSame(['13:00'], $dates[0]['time_slots']);
    }

    public function test_change_error_締め切り後は変更できないこと(): void
    {
        $policy = new ReservationSlotPolicy;
        $reservedAt = Carbon::parse('2026-07-11 12:00:00');
        $rules = $this->rules(changeDeadlineHours: 24);

        $this->assertNull($policy->changeError($reservedAt, Carbon::parse('2026-07-10 11:59:00'), $rules));
        $this->assertStringContainsString('24時間前まで', (string) $policy->changeError($reservedAt, Carbon::parse('2026-07-10 12:00:00'), $rules));
        $this->assertSame('予約時刻を過ぎたため変更・キャンセルできません', $policy->changeError($reservedAt, Carbon::parse('2026-07-11 12:00:00'), $this->rules()));
    }
}
