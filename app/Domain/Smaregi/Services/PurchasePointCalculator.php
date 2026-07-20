<?php

namespace App\Domain\Smaregi\Services;

/**
 * 購入金額と付与レートから獲得ポイントを算出する。
 */
final class PurchasePointCalculator
{
    public function calculate(float $amount, int $rate): int
    {
        if ($amount <= 0 || $rate <= 0) {
            return 0;
        }

        return (int) floor($amount / $rate);
    }
}
