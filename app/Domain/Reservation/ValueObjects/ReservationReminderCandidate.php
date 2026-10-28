<?php

namespace App\Domain\Reservation\ValueObjects;

/** 1回目か2回目のリマインドが未送信の、これから来店する予約。 */
final class ReservationReminderCandidate
{
    public function __construct(
        public readonly int $reservationId,
        public readonly int $projectId,
        public readonly ?int $storeId,
        public readonly ?string $storeName,
        /** 予約した会員。会員登録していなければ NULL */
        public readonly ?int $memberId,
        public readonly \DateTimeImmutable $reservedAt,
        public readonly \DateTimeImmutable $createdAt,
        public readonly ?int $numberOfPeople,
        public readonly ?string $courseName,
        public readonly bool $firstReminded = false,
        public readonly bool $secondReminded = false,
    ) {}
}
