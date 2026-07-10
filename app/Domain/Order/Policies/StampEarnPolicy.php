<?php

namespace App\Domain\Order\Policies;

final class StampEarnPolicy
{
    public function calculateCount(float $finalAmount, int $stampEarnPerAmount): int
    {
        if ($finalAmount <= 0 || $stampEarnPerAmount <= 0) {
            return 0;
        }

        return (int) floor($finalAmount / $stampEarnPerAmount);
    }
}
