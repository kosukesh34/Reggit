<?php

namespace App\Domain\Coupon\Aggregates;

use App\Domain\Coupon\ValueObjects\MemberCouponId;

final class MemberCoupon
{
    public const STATUS_UNUSED = 'unused';

    public const STATUS_USED = 'used';

    private bool $wasMarkedUsed = false;

    public function __construct(
        private MemberCouponId $id,
        private int $projectId,
        private int $memberId,
        private int $couponId,
        private string $status,
        private ?\DateTimeInterface $validFrom,
        private ?\DateTimeInterface $validUntil,
        private ?\DateTimeInterface $usedAt,
        private ?string $couponNameSnapshot,
        private ?string $couponName,
        private ?string $couponCode,
        private string $useDisplay,
    ) {}

    public function id(): MemberCouponId
    {
        return $this->id;
    }

    public function projectId(): int
    {
        return $this->projectId;
    }

    public function memberId(): int
    {
        return $this->memberId;
    }

    public function couponId(): int
    {
        return $this->couponId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function usedAt(): ?\DateTimeInterface
    {
        return $this->usedAt;
    }

    public function wasMarkedUsed(): bool
    {
        return $this->wasMarkedUsed;
    }

    public function displayName(): string
    {
        if ($this->couponNameSnapshot !== null && $this->couponNameSnapshot !== '') {
            return $this->couponNameSnapshot;
        }

        return $this->couponName ?? '';
    }

    public function couponCode(): ?string
    {
        return $this->couponCode;
    }

    public function useDisplay(): string
    {
        return $this->useDisplay;
    }

    public function markAsUsed(\DateTimeInterface $now): void
    {
        if ($this->status === self::STATUS_USED) {
            throw new \Exception('このクーポンは使用済みです');
        }

        if ($this->validFrom !== null && $this->validFrom->getTimestamp() > $now->getTimestamp()) {
            throw new \Exception('このクーポンはまだ利用開始前です');
        }

        if ($this->validUntil !== null && $this->validUntil->getTimestamp() < $now->getTimestamp()) {
            throw new \Exception('このクーポンは期限切れです');
        }

        $this->status = self::STATUS_USED;
        $this->usedAt = $now;
        $this->wasMarkedUsed = true;
    }
}
