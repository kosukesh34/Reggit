<?php

namespace App\Domain\Queue\Aggregates;

use App\Domain\Queue\ValueObjects\QueueEntryId;
use App\Enums\QueueStatus;

final class QueueEntry
{
    public function __construct(
        private QueueEntryId $id,
        private int $projectId,
        private string $status,
        private ?\DateTimeInterface $calledAt,
        private ?\DateTimeInterface $enteredAt,
    ) {}

    public function id(): QueueEntryId
    {
        return $this->id;
    }

    public function projectId(): int
    {
        return $this->projectId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function calledAt(): ?\DateTimeInterface
    {
        return $this->calledAt;
    }

    public function enteredAt(): ?\DateTimeInterface
    {
        return $this->enteredAt;
    }

    public function canCancel(): bool
    {
        return in_array($this->status, [QueueStatus::WAITING, QueueStatus::CALLED], true);
    }

    public function cancel(): void
    {
        if ($this->canCancel() === false) {
            return;
        }

        $this->status = QueueStatus::CANCELLED;
    }

    public function markAsCalled(\DateTimeInterface $now): void
    {
        if ($this->status !== QueueStatus::WAITING) {
            return;
        }

        $this->status = QueueStatus::CALLED;
        if ($this->calledAt === null) {
            $this->calledAt = $now;
        }
    }

    public function markAsEntered(\DateTimeInterface $now): void
    {
        if (in_array($this->status, [QueueStatus::CANCELLED, QueueStatus::ENTERED], true)) {
            return;
        }

        $this->status = QueueStatus::ENTERED;
        if ($this->enteredAt === null) {
            $this->enteredAt = $now;
        }
    }

    /**
     * 管理画面からのステータス更新（遷移制約は緩く、タイムスタンプのみ補完）。
     */
    public function applyAdminStatus(string $status, \DateTimeInterface $now): void
    {
        $this->status = $status;

        if ($status === QueueStatus::CALLED && $this->calledAt === null) {
            $this->calledAt = $now;
        }

        if ($status === QueueStatus::ENTERED && $this->enteredAt === null) {
            $this->enteredAt = $now;
        }
    }
}
