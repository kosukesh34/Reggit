<?php

namespace App\Domain\Reservation\Aggregates;

use App\Domain\Reservation\ValueObjects\ReservationId;
use App\Enums\ReservationStatus;

final class Reservation
{
    public function __construct(
        private ReservationId $id,
        private int $projectId,
        private int $userId,
        private string $status,
        private \DateTimeInterface $reservedAt,
        private int $numberOfPeople,
        private ?string $notes,
        private int $version = 0,
    ) {}

    public function version(): int
    {
        return $this->version;
    }

    public function id(): ReservationId
    {
        return $this->id;
    }

    public function projectId(): int
    {
        return $this->projectId;
    }

    public function userId(): int
    {
        return $this->userId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function reservedAt(): \DateTimeInterface
    {
        return $this->reservedAt;
    }

    public function numberOfPeople(): int
    {
        return $this->numberOfPeople;
    }

    public function notes(): ?string
    {
        return $this->notes;
    }

    public function canCancel(): bool
    {
        return $this->status !== ReservationStatus::CANCELLED
            && $this->status !== ReservationStatus::COMPLETED;
    }

    public function canEdit(): bool
    {
        return $this->status !== ReservationStatus::CANCELLED
            && $this->status !== ReservationStatus::COMPLETED;
    }

    public function applyAdminStatus(string $status): void
    {
        $this->status = $status;
    }

    public function complete(): void
    {
        $this->status = ReservationStatus::COMPLETED;
    }

    public function cancel(): void
    {
        if ($this->canCancel() === false) {
            throw new \Exception('この予約はキャンセルできません');
        }

        $this->status = ReservationStatus::CANCELLED;
    }

    public function updateDetails(
        \DateTimeInterface $reservedAt,
        int $numberOfPeople,
        ?string $notes,
    ): void {
        if ($this->canEdit() === false) {
            throw new \Exception('この予約は編集できません');
        }

        $this->reservedAt = $reservedAt;
        $this->numberOfPeople = $numberOfPeople;
        $this->notes = $notes;
    }
}
