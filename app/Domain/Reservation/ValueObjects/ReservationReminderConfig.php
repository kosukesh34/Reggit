<?php

namespace App\Domain\Reservation\ValueObjects;

/** 予約リマインドの設定。時間は「予約の何時間前か」で、0は送らない。 */
final class ReservationReminderConfig
{
    public function __construct(
        public readonly int $firstHours,
        public readonly int $secondHours,
        /** 本文。NULLなら標準の文面 */
        public readonly ?string $message,
    ) {}

    public static function disabled(): self
    {
        return new self(0, 0, null);
    }
}
