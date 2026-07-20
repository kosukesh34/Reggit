<?php

namespace App\Domain\Smaregi\Services;

use App\Domain\Smaregi\ValueObjects\SmaregiInboundTransaction;
use App\Enums\SmaregiInboundTransactionKind;
use Carbon\Carbon;

/**
 * スマレジ POS 取引ペイロードを取り込み用スナップショットへ分類する。
 */
final class SmaregiInboundTransactionClassifier
{
    public const SKIP_REASON_NOT_NORMAL_SALE = 'not_a_normal_sale';

    private const SALE_HEAD_DIVISION = '1';

    private const CANCEL_DIVISION_NORMAL = '0';

    private const CANCEL_DIVISION_CANCELLED = '1';

    private const RETURN_SALES_YES = '1';

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    public function classify(array $transaction): SmaregiInboundTransaction
    {
        $customerId = $this->extractCustomerId($transaction);
        $hasSmaregiManagedPointDelta = $this->hasSmaregiManagedPointDelta($transaction);
        $details = $this->extractDetails($transaction);

        if ($this->isCancelled($transaction)) {
            return new SmaregiInboundTransaction(
                SmaregiInboundTransactionKind::CANCELLED,
                $customerId,
                null,
                null,
                $hasSmaregiManagedPointDelta,
                $details,
            );
        }

        if (! $this->isNormalSaleHead($transaction)) {
            return new SmaregiInboundTransaction(
                SmaregiInboundTransactionKind::SKIP,
                $customerId,
                null,
                null,
                $hasSmaregiManagedPointDelta,
                $details,
                self::SKIP_REASON_NOT_NORMAL_SALE,
            );
        }

        $amount = $this->resolvePurchaseAmount($transaction);
        $visitedAt = $this->resolveVisitedAt($transaction);
        $kind = $this->isReturnSales($transaction)
            ? SmaregiInboundTransactionKind::RETURN
            : SmaregiInboundTransactionKind::SALE;

        return new SmaregiInboundTransaction(
            $kind,
            $customerId,
            $amount,
            $visitedAt,
            $hasSmaregiManagedPointDelta,
            $details,
        );
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function isCancelled(array $transaction): bool
    {
        $cancel = trim((string) ($transaction['cancelDivision'] ?? self::CANCEL_DIVISION_NORMAL));

        return $cancel === self::CANCEL_DIVISION_CANCELLED;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function isReturnSales(array $transaction): bool
    {
        return trim((string) ($transaction['returnSales'] ?? '0')) === self::RETURN_SALES_YES;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function isNormalSaleHead(array $transaction): bool
    {
        $division = trim((string) ($transaction['transactionHeadDivision'] ?? ''));
        if ($division !== self::SALE_HEAD_DIVISION) {
            return false;
        }

        $cancel = trim((string) ($transaction['cancelDivision'] ?? self::CANCEL_DIVISION_NORMAL));

        return $cancel === '' || $cancel === self::CANCEL_DIVISION_NORMAL;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function extractCustomerId(array $transaction): string
    {
        $customerId = $transaction['customerId'] ?? $transaction['customer_id'] ?? null;
        if ($customerId === null || trim((string) $customerId) === '') {
            $nested = $transaction['customer'] ?? null;
            if (is_array($nested)) {
                $customerId = $nested['customerId'] ?? $nested['customer_id'] ?? null;
            }
        }
        if ($customerId === null) {
            return '';
        }

        return trim((string) $customerId);
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function resolvePurchaseAmount(array $transaction): ?float
    {
        $raw = $transaction['total'] ?? $transaction['subtotal'] ?? null;
        if ($raw === null || $raw === '') {
            return null;
        }
        if (! is_numeric($raw)) {
            return null;
        }
        $amount = (float) $raw;
        if ($this->isReturnSales($transaction)) {
            return -1 * abs($amount);
        }

        return $amount;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function resolveVisitedAt(array $transaction): ?Carbon
    {
        foreach (['terminalTranDateTime', 'transactionDateTime'] as $key) {
            $raw = $transaction[$key] ?? null;
            if (! is_string($raw) || trim($raw) === '') {
                continue;
            }
            try {
                return Carbon::parse($raw);
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function hasSmaregiManagedPointDelta(array $transaction): bool
    {
        $newPoint = $this->parseOptionalInt($transaction['newPoint'] ?? null);

        return $newPoint !== null && $newPoint !== 0;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     * @return list<array<string, string|int|float|bool|array|null>>
     */
    private function extractDetails(array $transaction): array
    {
        $details = $transaction['details'] ?? null;
        if (! is_array($details) || $details === []) {
            return [];
        }

        $rows = [];
        foreach ($details as $detail) {
            if (is_array($detail)) {
                $rows[] = $detail;
            }
        }

        return $rows;
    }

    private function parseOptionalInt(string|int|float|bool|array|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }
        if (is_float($value)) {
            return (int) $value;
        }

        return null;
    }
}
