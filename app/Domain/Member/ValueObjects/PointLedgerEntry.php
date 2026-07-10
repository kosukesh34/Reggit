<?php

namespace App\Domain\Member\ValueObjects;

use App\Enums\PointTransactionType;

final class PointLedgerEntry
{
    private function __construct(
        public readonly string $type,
        public readonly int $signedPoints,
        public readonly ?string $description,
        public readonly ?int $orderId,
        public readonly ?\DateTimeInterface $expiresAt,
    ) {}

    public static function earned(
        int $points,
        ?string $description,
        ?int $orderId,
        \DateTimeInterface $expiresAt,
    ): self {
        return new self(
            PointTransactionType::EARNED,
            $points,
            $description,
            $orderId,
            $expiresAt,
        );
    }

    public static function used(
        int $points,
        ?string $description,
        ?int $orderId,
    ): self {
        return new self(
            PointTransactionType::USED,
            -abs($points),
            $description,
            $orderId,
            null,
        );
    }

    public static function refunded(
        int $points,
        ?string $description,
        int $orderId,
    ): self {
        return new self(
            PointTransactionType::REFUNDED,
            $points,
            $description,
            $orderId,
            null,
        );
    }

    public static function reversed(
        int $points,
        ?string $description,
        int $orderId,
    ): self {
        return new self(
            PointTransactionType::REVERSED,
            -abs($points),
            $description,
            $orderId,
            null,
        );
    }
}
