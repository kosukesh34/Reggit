<?php

namespace Tests\Unit\Domain\Reservation;

use App\Domain\Reservation\Aggregates\Reservation;
use App\Domain\Reservation\ValueObjects\ReservationId;
use App\Enums\ReservationStatus;
use Carbon\Carbon;
use Tests\TestCase;

class ReservationAggregateTest extends TestCase
{
    private function createReservation(string $status = ReservationStatus::PENDING): Reservation
    {
        return new Reservation(
            new ReservationId(1),
            1,
            10,
            $status,
            Carbon::parse('2026-08-01 12:00:00'),
            2,
            'メモ',
        );
    }

    public function test_cancel_予約をキャンセルできること(): void
    {
        $reservation = $this->createReservation();

        $reservation->cancel();

        $this->assertSame(ReservationStatus::CANCELLED, $reservation->status());
    }

    public function test_cancel_完了済みは拒否されること(): void
    {
        $reservation = $this->createReservation(ReservationStatus::COMPLETED);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('この予約はキャンセルできません');

        $reservation->cancel();
    }

    public function test_updateDetails_編集できること(): void
    {
        $reservation = $this->createReservation();
        $newAt = Carbon::parse('2026-08-02 18:00:00');

        $reservation->updateDetails($newAt, 4, '更新メモ');

        $this->assertSame(4, $reservation->numberOfPeople());
        $this->assertSame('更新メモ', $reservation->notes());
        $this->assertSame($newAt->getTimestamp(), $reservation->reservedAt()->getTimestamp());
    }

    public function test_updateDetails_キャンセル済みは拒否されること(): void
    {
        $reservation = $this->createReservation(ReservationStatus::CANCELLED);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('この予約は編集できません');

        $reservation->updateDetails(Carbon::now(), 1, null);
    }
}
