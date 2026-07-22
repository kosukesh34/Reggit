<?php

namespace App\Services\Smaregi;

use App\Models\Product;
use App\Util\NumberHelper;
use App\Util\StrHelper;

/**
 * スマレジ取引明細の共通パース処理。
 */
final class SmaregiInboundDetailHelper
{
    public static function transactionHeadId(array $result): string
    {
        return StrHelper::trimmedOrEmpty($result['transactionHeadId'] ?? $result['transaction_head_id'] ?? null);
    }

    /**
     * @return array<string, int|string>
     */
    public static function productIdsBySmaregiProductId(int $projectId): array
    {
        return Product::query()
            ->where('project_id', $projectId)
            ->whereNotNull('smaregi_product_id')
            ->pluck('id', 'smaregi_product_id')
            ->all();
    }

    /**
     * @param  array<string, int|string>  $productIdBySmaregi
     */
    public static function resolveLocalProductId(string $smaregiProductId, array $productIdBySmaregi): ?int
    {
        if ($smaregiProductId === '' || ! isset($productIdBySmaregi[$smaregiProductId])) {
            return null;
        }

        return (int) $productIdBySmaregi[$smaregiProductId];
    }

    public static function nullableSmaregiProductId(string $smaregiProductId): ?string
    {
        return $smaregiProductId !== '' ? $smaregiProductId : null;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $detail
     * @return array{productName: string, smaregiProductId: string, quantity: int, unitPrice: float}|null
     */
    public static function parseLineItem(array $detail): ?array
    {
        $productName = StrHelper::trimmedOrEmpty($detail['productName'] ?? $detail['printReceiptProductName'] ?? null);
        if ($productName === '') {
            return null;
        }

        return [
            'productName' => $productName,
            'smaregiProductId' => StrHelper::trimmedOrEmpty($detail['productId'] ?? null),
            'quantity' => NumberHelper::parsePositiveInt($detail['quantity'] ?? 1) ?? 1,
            'unitPrice' => NumberHelper::parseMoney($detail['salesPrice'] ?? $detail['price'] ?? 0) ?? 0.0,
        ];
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $detail
     */
    public static function resolveLineAmount(array $detail, float $unitPrice, int $quantity): float
    {
        $lineAmount = NumberHelper::parseMoney($detail['unitDiscountedSum'] ?? $detail['unitNonDiscountSum'] ?? null);
        if ($lineAmount !== null) {
            return $lineAmount;
        }

        return $unitPrice * $quantity;
    }
}
