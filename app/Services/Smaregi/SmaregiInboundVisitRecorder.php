<?php

namespace App\Services\Smaregi;

use App\Enums\MemberVisitSource;
use App\Models\Member;
use App\Models\MemberVisit;
use App\Models\MemberVisitItem;
use App\Models\Product;
use App\Models\Scopes\ProjectScope;
use App\Services\Admin\MemberAdminService;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
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
    ): ?MemberVisit {
        return DB::transaction(function () use ($member, $headId, $visitedAt, $amount, $details): ?MemberVisit {
            $member = $member->newQuery()->withoutGlobalScope(ProjectScope::class)
                ->where('project_id', (int) $member->project_id)->whereKey($member->id)
                ->lockForUpdate()->firstOrFail();
            $visit = MemberVisit::withoutGlobalScopes()
                ->where('project_id', $member->project_id)
                ->where('smaregi_transaction_head_id', $headId)
                ->lockForUpdate()->first();

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
                        ->lockForUpdate()->first();
                    if ($visit === null) {
                        throw $e;
                    }
                }
            }

            if ($visit !== null) {
                $this->saveVisitItems($visit, $details);
            }

            return $visit;
        }, 3);
    }

    public function attachOrder(MemberVisit $visit, int $orderId): void
    {
        if ($visit->order_id !== null) {
            return;
        }
        $visit->order_id = $orderId;
        $visit->save();
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

        $rows = [];
        foreach ($details as $detail) {
            $row = $this->buildVisitItemRow($visit, $detail, $productIdBySmaregi, count($rows));
            if ($row !== null) {
                $rows[] = $row;
            }
        }
        if ($rows === []) {
            return;
        }

        $columns = array_keys($rows[0]);
        $existing = $visit->items()->lockForUpdate()->get()
            ->map(fn (MemberVisitItem $item) => $item->only($columns))->all();
        if ($existing == $rows) {
            return;
        }

        // A retry also repairs details left partially saved by the legacy writer.
        $visit->items()->delete();
        foreach ($rows as $row) {
            MemberVisitItem::create($row);
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
            'unit_price' => round($unitPrice, 2),
            'amount' => round($lineAmount, 2),
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
