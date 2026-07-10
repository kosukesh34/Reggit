<?php

namespace App\Domain\Order\Policies;

final class ProductAvailabilityPolicy
{
    public function assertAvailable(string $productName, bool $isAvailable, ?int $stock, int $quantity): void
    {
        if ($isAvailable === false) {
            throw new \Exception("商品 {$productName} は現在利用できません");
        }

        if ($stock !== null && $stock < $quantity) {
            throw new \Exception("商品 {$productName} の在庫が不足しています");
        }
    }
}
