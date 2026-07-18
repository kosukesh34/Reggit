<?php

namespace App\Services\Smaregi;

use App\Enums\MemberVisitSource;
use App\Models\Member;
use App\Models\MemberVisit;
use App\Models\MemberVisitItem;
use App\Models\Product;
use App\Models\Project;
use App\Models\Settings\ProjectProfileSmaregiSetting;
use App\Models\SmaregiContract;
use App\Services\Admin\MemberAdminService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/**
 * スマレジ POS の通常販売取引を来店・購入履歴として取り込む。
 */
class SmaregiTransactionInboundSyncService
{
    private const SALE_HEAD_DIVISION = '1';

    private const CANCEL_DIVISION_NORMAL = '0';

    public function __construct(
        private SmaregiApiService $smaregiApi,
        private MemberAdminService $memberAdminService,
    ) {}

    /**
     * @param  list<string>  $transactionHeadIds
     */
    public function syncByTransactionHeadIds(string $contractId, array $transactionHeadIds): void
    {
        $contractId = trim($contractId);
        if ($contractId === '' || $transactionHeadIds === []) {
            return;
        }

        foreach ($transactionHeadIds as $rawHeadId) {
            $headId = trim((string) $rawHeadId);
            if ($headId === '') {
                continue;
            }
            $this->syncOneTransaction($contractId, $headId);
        }
    }

    private function syncOneTransaction(string $contractId, string $headId): void
    {
        $transaction = $this->smaregiApi->getTransaction($contractId, $headId, true);
        if ($transaction === null) {
            Log::warning('[Smaregi] Inbound transaction sync: getTransaction failed', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        if (! $this->isNormalSaleTransaction($transaction)) {
            Log::info('[Smaregi] Inbound transaction sync skipped: not a normal sale', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
                'transaction_head_division' => $transaction['transactionHeadDivision'] ?? null,
                'cancel_division' => $transaction['cancelDivision'] ?? null,
            ]);

            return;
        }

        $customerId = $this->extractCustomerId($transaction);
        if ($customerId === '') {
            Log::info('[Smaregi] Inbound transaction sync skipped: no customerId', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        $amount = $this->resolvePurchaseAmount($transaction);
        if ($amount === null) {
            Log::info('[Smaregi] Inbound transaction sync skipped: no purchase amount', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        $visitedAt = $this->resolveVisitedAt($transaction);
        if ($visitedAt === null) {
            Log::warning('[Smaregi] Inbound transaction sync skipped: no transaction datetime', [
                'smaregi_contract_id' => $contractId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        $members = $this->findMembers($contractId, $customerId);
        if ($members === []) {
            Log::info('[Smaregi] Inbound transaction sync: ローカル会員なし', [
                'smaregi_contract_id' => $contractId,
                'smaregi_customer_id' => $customerId,
                'transaction_head_id' => $headId,
            ]);

            return;
        }

        foreach ($members as $member) {
            $this->recordVisitWithItems($member, $headId, $visitedAt, $amount, $transaction);
        }
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function isNormalSaleTransaction(array $transaction): bool
    {
        $division = trim((string) ($transaction['transactionHeadDivision'] ?? ''));
        if ($division !== self::SALE_HEAD_DIVISION) {
            return false;
        }

        $cancelDivision = $transaction['cancelDivision'] ?? self::CANCEL_DIVISION_NORMAL;
        $cancel = trim((string) $cancelDivision);

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
        $returnSales = trim((string) ($transaction['returnSales'] ?? '0'));
        if ($returnSales === '1') {
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
     * @return list<Member>
     */
    private function findMembers(string $contractId, string $smaregiCustomerId): array
    {
        $projectIds = $this->projectIdsForContract($contractId);
        if ($projectIds === []) {
            return [];
        }

        return Member::query()
            ->whereIn('project_id', $projectIds)
            ->where('smaregi_customer_id', $smaregiCustomerId)
            ->get()
            ->all();
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function recordVisitWithItems(
        Member $member,
        string $headId,
        Carbon $visitedAt,
        float $amount,
        array $transaction,
    ): void {
        $visit = MemberVisit::withoutGlobalScopes()
            ->where('project_id', $member->project_id)
            ->where('smaregi_transaction_head_id', $headId)
            ->first();

        if ($visit === null) {
            try {
                $visit = $this->memberAdminService->recordVisit(
                    $member,
                    $visitedAt,
                    MemberVisitSource::SMAREGI,
                    null,
                    'スマレジ取引 #'.$headId,
                    $amount,
                    $headId,
                );
                Log::info('[Smaregi] Inbound transaction sync recorded', [
                    'member_id' => (int) $member->id,
                    'project_id' => (int) $member->project_id,
                    'transaction_head_id' => $headId,
                    'amount' => $amount,
                    'visited_at' => $visitedAt->toIso8601String(),
                ]);
            } catch (UniqueConstraintViolationException $e) {
                $visit = MemberVisit::withoutGlobalScopes()
                    ->where('project_id', $member->project_id)
                    ->where('smaregi_transaction_head_id', $headId)
                    ->first();
                if ($visit === null) {
                    throw $e;
                }
            }
        }

        if ($visit->items()->exists()) {
            return;
        }

        $this->saveVisitItems($visit, $transaction);
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $transaction
     */
    private function saveVisitItems(MemberVisit $visit, array $transaction): void
    {
        $details = $transaction['details'] ?? null;
        if (! is_array($details) || $details === []) {
            return;
        }

        $productIdBySmaregi = Product::query()
            ->where('project_id', $visit->project_id)
            ->whereNotNull('smaregi_product_id')
            ->pluck('id', 'smaregi_product_id')
            ->all();

        $sortOrder = 0;
        foreach ($details as $detail) {
            if (! is_array($detail)) {
                continue;
            }
            $row = $this->buildVisitItemRow($visit, $detail, $productIdBySmaregi, $sortOrder);
            if ($row === null) {
                continue;
            }
            MemberVisitItem::create($row);
            $sortOrder++;
        }
    }

    /**
     * @param  array<string, string|int|float|bool|array|null>  $detail
     * @param  array<string, int|string>  $productIdBySmaregi
     * @return array<string, int|float|string|null>|null
     */
    private function buildVisitItemRow(
        MemberVisit $visit,
        array $detail,
        array $productIdBySmaregi,
        int $sortOrder,
    ): ?array {
        $productName = trim((string) ($detail['productName'] ?? $detail['printReceiptProductName'] ?? ''));
        if ($productName === '') {
            return null;
        }

        $smaregiProductId = trim((string) ($detail['productId'] ?? ''));
        $quantity = $this->parsePositiveInt($detail['quantity'] ?? 1) ?? 1;
        $unitPrice = $this->parseMoney($detail['salesPrice'] ?? $detail['price'] ?? 0) ?? 0.0;
        $lineAmount = $this->parseMoney($detail['unitDiscountedSum'] ?? $detail['unitNonDiscountSum'] ?? null);
        if ($lineAmount === null) {
            $lineAmount = $unitPrice * $quantity;
        }

        $localProductId = null;
        if ($smaregiProductId !== '' && isset($productIdBySmaregi[$smaregiProductId])) {
            $localProductId = (int) $productIdBySmaregi[$smaregiProductId];
        }

        return [
            'project_id' => (int) $visit->project_id,
            'member_visit_id' => (int) $visit->id,
            'product_id' => $localProductId,
            'smaregi_product_id' => $smaregiProductId !== '' ? $smaregiProductId : null,
            'product_name' => $productName,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'amount' => $lineAmount,
            'sort_order' => $sortOrder,
        ];
    }

    /**
     * @param  string|int|float|bool|array|null  $value
     */
    private function parsePositiveInt($value): ?int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }
        if (is_string($value) && $value !== '' && is_numeric($value)) {
            $parsed = (int) $value;

            return $parsed > 0 ? $parsed : null;
        }
        if (is_float($value) && $value > 0) {
            return (int) round($value);
        }

        return null;
    }

    /**
     * @param  string|int|float|bool|array|null  $value
     */
    private function parseMoney($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /**
     * @return list<int>
     */
    private function projectIdsForContract(string $contractId): array
    {
        $fromSettings = ProjectProfileSmaregiSetting::query()
            ->where('smaregi_contract_id', $contractId)
            ->pluck('project_id')
            ->all();
        $fromContracts = SmaregiContract::query()
            ->where('contract_id', $contractId)
            ->whereNotNull('project_id')
            ->pluck('project_id')
            ->all();

        $ids = array_values(array_unique(array_map('intval', array_merge($fromSettings, $fromContracts))));
        if ($ids !== []) {
            return $ids;
        }

        $fromEffectiveContract = [];
        foreach (Project::query()->pluck('id') as $projectId) {
            $effective = $this->smaregiApi->getContractIdForProject((int) $projectId);
            if (is_string($effective) && trim($effective) === $contractId) {
                $fromEffectiveContract[] = (int) $projectId;
            }
        }

        return array_values(array_unique($fromEffectiveContract));
    }
}
