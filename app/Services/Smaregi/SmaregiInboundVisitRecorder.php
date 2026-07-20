<?php

namespace App\Services\Smaregi;

use App\Enums\MemberVisitSource;
use App\Models\Member;
use App\Models\MemberVisit;
use App\Models\MemberVisitItem;
use App\Models\Product;
use App\Services\Admin\MemberAdminService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Log;

/**
 * スマレジ取引の来店・購入明細を記録する。
 */
class SmaregiInboundVisitRecorder
{
    public function __construct(
        private MemberAdminService $memberAdminService,
    ) {}

    /**
     * @param  list<array<string, string|int|float|bool|array|null>>  $details
     */
    public function record(
        Member $member,
        string $headId,
        Carbon $visitedAt,
        float $amount,
        array $details,
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

        $this->saveVisitItems($visit, $details);
    }

    public function deleteForTransaction(Member $member, string $headId): void
    {
        $visit = MemberVisit::withoutGlobalScopes()
            ->where('project_id', $member->project_id)
            ->where('member_id', $member->id)
            ->where('smaregi_transaction_head_id', $headId)
            ->first();
        if ($visit === null) {
            return;
        }
        $visit->items()->delete();
        $visit->delete();
    }

    /**
     * @param  list<array<string, string|int|float|bool|array|null>>  $details
     */
    private function saveVisitItems(MemberVisit $visit, array $details): void
    {
        if ($details === []) {
            return;
        }

        $productIdBySmaregi = Product::query()
            ->where('project_id', $visit->project_id)
            ->whereNotNull('smaregi_product_id')
            ->pluck('id', 'smaregi_product_id')
            ->all();

        $sortOrder = 0;
        foreach ($details as $detail) {
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

    private function parsePositiveInt(string|int|float|bool|array|null $value): ?int
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

    private function parseMoney(string|int|float|bool|array|null $value): ?float
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
}
