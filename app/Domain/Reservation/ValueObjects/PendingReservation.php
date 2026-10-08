<?php

namespace App\Domain\Reservation\ValueObjects;

final class PendingReservation
{
    public function __construct(
        public readonly int $projectId,
        public readonly int $userId,
        public readonly \DateTimeInterface $reservedAt,
        public readonly int $numberOfPeople,
        public readonly ?string $notes,
        public readonly ?int $storeId = null,
    ) {}
}
