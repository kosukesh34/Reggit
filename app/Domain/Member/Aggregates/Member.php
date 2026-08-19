<?php

namespace App\Domain\Member\Aggregates;

use App\Domain\Member\ValueObjects\MemberId;
use App\Domain\Member\ValueObjects\PointLedgerEntry;

final class Member
{
    /** @var list<PointLedgerEntry> */
    private array $pendingEntries = [];

    public function __construct(
        private MemberId $id,
        private int $projectId,
        private int $ledgerBalance,
        private string $smaregiCustomerId,
    ) {}

    public function id(): MemberId
    {
        return $this->id;
    }

    public function projectId(): int
    {
        return $this->projectId;
    }

    public function smaregiCustomerId(): string
    {
        return $this->smaregiCustomerId;
    }

    public function displayPoints(): int
    {
        return $this->ledgerBalance;
    }

    public function earnPoints(
        int $points,
        ?string $description,
        ?int $orderId,
        \DateTimeInterface $expiresAt,
    ): void {
        if ($points <= 0) {
            return;
        }

        $this->pendingEntries[] = PointLedgerEntry::earned(
            $points,
            $description,
            $orderId,
            $expiresAt,
        );
        $this->ledgerBalance += $points;
    }

    public function usePoints(int $points, ?string $description, ?int $orderId): void
    {
        if ($points <= 0) {
            return;
        }

        if ($this->ledgerBalance <= 0) {
            return;
        }

        $points = min($points, $this->ledgerBalance);
        $this->pendingEntries[] = PointLedgerEntry::used($points, $description, $orderId);
        $this->ledgerBalance -= $points;
    }

    public function refundUsedPointsForOrder(int $orderId, int $points, ?string $description): void
    {
        if ($points <= 0) {
            return;
        }

        $this->pendingEntries[] = PointLedgerEntry::refunded($points, $description, $orderId);
        $this->ledgerBalance += $points;
    }

    public function reverseEarnedPoints(int $points, ?string $description, ?int $orderId = null): void
    {
        if ($points <= 0) {
            return;
        }

        if ($this->ledgerBalance <= 0) {
            return;
        }

        $points = min($points, $this->ledgerBalance);
        $this->pendingEntries[] = PointLedgerEntry::reversed($points, $description, $orderId);
        $this->ledgerBalance -= $points;
    }

    public function reverseEarnedPointsForOrder(int $orderId, int $points, ?string $description): void
    {
        $this->reverseEarnedPoints($points, $description, $orderId);
    }

    /** @return list<PointLedgerEntry> */
    public function pullPendingEntries(): array
    {
        $entries = $this->pendingEntries;
        $this->pendingEntries = [];

        return $entries;
    }
}
