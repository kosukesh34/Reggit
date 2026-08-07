<?php

namespace App\Services\Smaregi;

use Illuminate\Support\Facades\Log;

/**
 * Webhook の transactionHeadIds から会員IDと取引後ポイントを解決する。
 */
class SmaregiTransactionCustomerResolver
{
    private const RESOLVE_ATTEMPTS = 3;

    private const RESOLVE_DELAY_MICROSECONDS = 400000;

    public function __construct(
        private SmaregiApiClient $client,
    ) {}

    /**
     * ポイント更新直後は customerId が未反映のことがあるため、短時間リトライする。
     * totalPoint が取れた場合はポイント同期のヒントとして返す（会員ポイントAPI失敗時のフォールバック）。
     *
     * @param  list<string|int>  $transactionHeadIds
     * @return array{customer_ids: list<string>, points_by_customer_id: array<string, int>}
     */
    public function resolveCustomersFromTransactionHeads(string $contractId, array $transactionHeadIds): array
    {
        $customerIds = [];
        $pointsByCustomerId = [];
        foreach ($transactionHeadIds as $rawHeadId) {
            $headId = trim((string) $rawHeadId);
            if ($headId === '') {
                continue;
            }
            $resolved = $this->resolveOneTransactionCustomer($contractId, $headId);
            if ($resolved === null) {
                continue;
            }
            $customerIds[] = $resolved['customer_id'];
            if ($resolved['total_point'] !== null) {
                $pointsByCustomerId[$resolved['customer_id']] = $resolved['total_point'];
            }
        }

        return [
            'customer_ids' => array_values(array_unique($customerIds)),
            'points_by_customer_id' => $pointsByCustomerId,
        ];
    }

    /**
     * @return array{customer_id: string, total_point: int|null}|null
     */
    private function resolveOneTransactionCustomer(string $contractId, string $headId): ?array
    {
        $transaction = null;
        $customerId = '';
        for ($attempt = 1; $attempt <= self::RESOLVE_ATTEMPTS; $attempt++) {
            $transaction = $this->client->request($contractId, 'GET', 'pos/transactions/'.$headId);
            if ($transaction === null) {
                Log::warning('[Smaregi] resolveCustomersFromTransactionHeads: getTransaction failed', [
                    'smaregi_contract_id' => $contractId,
                    'transaction_head_id' => $headId,
                    'attempt' => $attempt,
                ]);
                if ($attempt < self::RESOLVE_ATTEMPTS) {
                    usleep($this->resolveDelayMicroseconds());
                }

                continue;
            }

            $customerId = $this->extractCustomerIdFromTransaction($transaction);
            if ($customerId !== '') {
                break;
            }
            if ($attempt < self::RESOLVE_ATTEMPTS) {
                usleep($this->resolveDelayMicroseconds());
            }
        }

        if ($transaction === null) {
            return null;
        }
        if ($customerId === '') {
            Log::info('[Smaregi] resolveCustomersFromTransactionHeads: transaction has no customerId', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
                'transaction_head_division' => $transaction['transactionHeadDivision'] ?? null,
                'total' => $transaction['total'] ?? null,
                'new_point' => $transaction['newPoint'] ?? null,
                'total_point' => $transaction['totalPoint'] ?? null,
            ]);

            return null;
        }

        return [
            'customer_id' => $customerId,
            'total_point' => $this->parseOptionalInt($transaction['totalPoint'] ?? null),
        ];
    }

    private function resolveDelayMicroseconds(): int
    {
        if (app()->environment('testing')) {
            return 0;
        }

        return self::RESOLVE_DELAY_MICROSECONDS;
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function extractCustomerIdFromTransaction(array $transaction): string
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

    private function parseOptionalInt(string|int|float|bool|array|null $value): ?int
    {
        if ($value === null) {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) round($value);
        }
        if (is_string($value) && $value !== '' && is_numeric($value)) {
            return (int) round((float) $value);
        }

        return null;
    }
}
