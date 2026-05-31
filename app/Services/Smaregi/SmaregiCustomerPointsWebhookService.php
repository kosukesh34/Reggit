<?php

namespace App\Services\Smaregi;

use App\Enums\PointTransactionType;
use App\Enums\SmaregiTransactionHeadDivision;
use App\Models\Member;
use App\Models\PointTransaction;
use App\Models\ShopSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SmaregiCustomerPointsWebhookService
{
    private const DESC_POINT_ADD = 'スマレジでのポイント加算';

    private const DESC_POINT_SUBTRACT = 'スマレジでのポイント減算';

    private const DESC_POINT_EXPIRE = 'スマレジでのポイント失効';

    private const DESC_POINT_PURCHASE = 'スマレジでの購買';

    private const DESC_POINT_DEFAULT = 'スマレジからのポイント変更';

    public function __construct(
        private SmaregiApiService $smaregiApi,
    ) {}

    /**
     * @param  array<int|string, string|int|null>  $transactionHeadIds
     */
    public function process(string $contractId, array $transactionHeadIds): void
    {
        $contractId = trim($contractId);
        if ($contractId === '') {
            Log::warning('[Smaregi] Webhook customer-points missing contractId');

            return;
        }

        $projectId = $this->smaregiApi->getProjectIdForContract($contractId);
        if ($projectId === null) {
            Log::warning('[Smaregi] Webhook customer-points project not found', [
                'project_id' => null,
                'smaregi_contract_id' => $contractId,
            ]);

            return;
        }

        $uniqueIds = [];
        foreach ($transactionHeadIds as $transactionHeadId) {
            $normalized = trim((string) $transactionHeadId);
            if ($normalized !== '') {
                $uniqueIds[$normalized] = $normalized;
            }
        }

        foreach ($uniqueIds as $transactionHeadId) {
            $this->processTransaction($contractId, $projectId, $transactionHeadId);
        }
    }

    private function processTransaction(string $contractId, int $projectId, string $transactionHeadId): void
    {
        if (PointTransaction::withoutGlobalScopes()->where('smaregi_transaction_head_id', $transactionHeadId)->exists()) {
            return;
        }

        $transaction = $this->smaregiApi->getTransaction($contractId, $transactionHeadId);
        if ($transaction === null) {
            Log::warning('[Smaregi] Webhook customer-points transaction fetch failed', [
                'project_id' => $projectId,
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $transactionHeadId,
            ]);

            return;
        }

        if ((string) ($transaction['cancelDivision'] ?? '0') === '1') {
            return;
        }

        $customerId = trim((string) ($transaction['customerId'] ?? ''));
        if ($customerId === '') {
            return;
        }

        $member = Member::withoutGlobalScopes()
            ->where('project_id', $projectId)
            ->where('smaregi_customer_id', $customerId)
            ->first();
        if ($member === null) {
            Log::info('[Smaregi] Webhook customer-points member not found locally', [
                'project_id' => $projectId,
                'smaregi_customer_id' => $customerId,
                'transaction_head_id' => $transactionHeadId,
            ]);

            return;
        }

        $delta = $this->resolvePointDelta($transaction);
        if ($delta === 0) {
            return;
        }

        $type = $this->resolvePointType($transaction, $delta);
        $description = $this->resolveDescription($transaction);

        DB::transaction(function () use ($member, $projectId, $type, $delta, $description, $transactionHeadId): void {
            if (PointTransaction::withoutGlobalScopes()->where('smaregi_transaction_head_id', $transactionHeadId)->exists()) {
                return;
            }

            PointTransaction::create([
                'project_id' => $projectId,
                'member_id' => $member->id,
                'type' => $type,
                'points' => $delta,
                'description' => $description,
                'smaregi_transaction_head_id' => $transactionHeadId,
                'expires_at' => $type === PointTransactionType::EARNED
                    ? now()->addDays(ShopSetting::getPointsValidDays($projectId))
                    : null,
            ]);
            $member->recalcPointsFromTransactions();
        });
    }

    /**
     * @param  array{
     *     point?: string|int,
     *     totalPoint?: string|int,
     *     transactionHeadDivision?: string|int,
     *     newPoint?: string|int,
     *     spendPoint?: string|int,
     *     amount?: string|int,
     * } $transaction スマレジAPIのトランザクションデータ
     */
    private function resolvePointDelta(array $transaction): int
    {
        $before = (int) ($transaction['point'] ?? 0);
        $after = (int) ($transaction['totalPoint'] ?? 0);
        if ($after !== $before) {
            return $after - $before;
        }

        $division = (string) ($transaction['transactionHeadDivision'] ?? '');
        if ($division === SmaregiTransactionHeadDivision::POINT_ADD) {
            return abs((int) ($transaction['newPoint'] ?? $transaction['amount'] ?? 0));
        }
        if ($division === SmaregiTransactionHeadDivision::POINT_SUBTRACT) {
            return -abs((int) ($transaction['spendPoint'] ?? $transaction['amount'] ?? 0));
        }
        if ($division === SmaregiTransactionHeadDivision::POINT_EXPIRE) {
            $expired = (int) ($transaction['spendPoint'] ?? $transaction['amount'] ?? 0);

            return $expired !== 0 ? -abs($expired) : 0;
        }

        return (int) ($transaction['newPoint'] ?? 0) - (int) ($transaction['spendPoint'] ?? 0);
    }

    /**
     * @param  array{
     *     transactionHeadDivision?: string|int,
     * } $transaction スマレジAPIのトランザクションデータ
     */
    private function resolvePointType(array $transaction, int $delta): string
    {
        if ((string) ($transaction['transactionHeadDivision'] ?? '') === SmaregiTransactionHeadDivision::POINT_EXPIRE) {
            return PointTransactionType::EXPIRED;
        }

        return $delta > 0 ? PointTransactionType::EARNED : PointTransactionType::USED;
    }

    /**
     * @param  array{
     *     transactionHeadDivision?: string|int,
     * } $transaction スマレジAPIのトランザクションデータ
     */
    private function resolveDescription(array $transaction): string
    {
        $division = (string) ($transaction['transactionHeadDivision'] ?? '');
        if ($division === SmaregiTransactionHeadDivision::POINT_ADD) {
            return self::DESC_POINT_ADD;
        }
        if ($division === SmaregiTransactionHeadDivision::POINT_SUBTRACT) {
            return self::DESC_POINT_SUBTRACT;
        }
        if ($division === SmaregiTransactionHeadDivision::POINT_EXPIRE) {
            return self::DESC_POINT_EXPIRE;
        }
        if ($division === SmaregiTransactionHeadDivision::PURCHASE) {
            return self::DESC_POINT_PURCHASE;
        }

        return self::DESC_POINT_DEFAULT;
    }
}
