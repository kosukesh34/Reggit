<?php

namespace App\Domain\Coupon\Policies;

final class CouponIssuePolicy
{
    public function isWithinIssuePeriod(
        ?\DateTimeInterface $startAt,
        ?\DateTimeInterface $endAt,
        \DateTimeInterface $now,
    ): bool {
        if ($startAt !== null && $startAt > $now) {
            return false;
        }

        if ($endAt !== null && $endAt < $now) {
            return false;
        }

        return true;
    }

    public function canIssueOnce(
        bool $alreadyIssued,
        ?\DateTimeInterface $startAt,
        ?\DateTimeInterface $endAt,
        \DateTimeInterface $now,
    ): bool {
        if ($alreadyIssued) {
            return false;
        }

        return $this->isWithinIssuePeriod($startAt, $endAt, $now);
    }

    public function canIssueBirthdayThisYear(
        bool $alreadyIssuedThisYear,
        ?\DateTimeInterface $startAt,
        ?\DateTimeInterface $endAt,
        \DateTimeInterface $now,
    ): bool {
        if ($alreadyIssuedThisYear) {
            return false;
        }

        return $this->isWithinIssuePeriod($startAt, $endAt, $now);
    }

    /**
     * @return array{0: \DateTimeInterface, 1: ?\DateTimeInterface}
     */
    public function resolveValidityWindow(
        ?\DateTimeInterface $startAt,
        ?\DateTimeInterface $endAt,
        \DateTimeInterface $now,
    ): array {
        return [$startAt ?? $now, $endAt];
    }
}
