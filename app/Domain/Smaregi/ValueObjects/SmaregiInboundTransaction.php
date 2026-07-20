<?php

namespace App\Domain\Smaregi\ValueObjects;

use App\Enums\SmaregiInboundTransactionKind;
use Carbon\Carbon;

/**
 * スマレジ POS 取引の取り込み用スナップショット。
 */
final class SmaregiInboundTransaction
{
    /**
     * @param  list<array<string, string|int|float|bool|array|null>>  $details
     */
    public function __construct(
        private string $kind,
        private string $customerId,
        private ?float $signedAmount,
        private ?Carbon $visitedAt,
        private bool $hasSmaregiManagedPointDelta,
        private array $details,
        private ?string $skipReason = null,
    ) {}

    public function kind(): string
    {
        return $this->kind;
    }

    public function customerId(): string
    {
        return $this->customerId;
    }

    public function signedAmount(): ?float
    {
        return $this->signedAmount;
    }

    public function absAmount(): float
    {
        return abs((float) ($this->signedAmount ?? 0.0));
    }

    public function visitedAt(): ?Carbon
    {
        return $this->visitedAt;
    }

    public function hasSmaregiManagedPointDelta(): bool
    {
        return $this->hasSmaregiManagedPointDelta;
    }

    /**
     * @return list<array<string, string|int|float|bool|array|null>>
     */
    public function details(): array
    {
        return $this->details;
    }

    public function skipReason(): ?string
    {
        return $this->skipReason;
    }

    public function isCancelled(): bool
    {
        return $this->kind === SmaregiInboundTransactionKind::CANCELLED;
    }

    public function isReturn(): bool
    {
        return $this->kind === SmaregiInboundTransactionKind::RETURN;
    }

    public function isSale(): bool
    {
        return $this->kind === SmaregiInboundTransactionKind::SALE;
    }

    public function isSkip(): bool
    {
        return $this->kind === SmaregiInboundTransactionKind::SKIP;
    }
}
