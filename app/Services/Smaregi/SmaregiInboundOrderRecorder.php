<?php

namespace App\Services\Smaregi;

use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Scopes\ProjectScope;
use App\Services\OrderNumberAssigner;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * POS 取引を awards / 在庫なしの Order として記録する。
 */
class SmaregiInboundOrderRecorder
{
    public function __construct(
        private OrderRepository $orderRepository,
        private OrderNumberAssigner $orderNumberAssigner,
    ) {}

    /**
     * @param  list<array<string, string|int|float|bool|array|null>>  $details
     */
    public function recordSale(
        Member $member,
        string $headId,
        float $amount,
        array $details,
        Carbon $visitedAt,
    ): ?Order {
        $projectId = (int) $member->project_id;
        $existingId = $this->orderRepository->findIdBySmaregiTransactionHeadId($projectId, $headId);
        if ($existingId !== null) {
            return Order::query()
                ->withoutGlobalScope(ProjectScope::class)
                ->whereKey($existingId)
                ->first();
        }

        $userId = $member->user_id !== null ? (int) $member->user_id : null;
        if ($userId === null) {
            Log::info('[Smaregi] Inbound order skipped: member has no user_id', [
                'member_id' => (int) $member->id,
                'project_id' => $projectId,
                'transaction_head_id' => $headId,
            ]);

            return null;
        }

        try {
            $order = DB::transaction(function () use ($member, $headId, $amount, $details, $visitedAt, $projectId, $userId): Order {
                $order = Order::withoutGlobalScope(ProjectScope::class)->create([
                    'project_id' => $projectId,
                    'user_id' => $userId,
                    'order_type' => OrderType::POS,
                    'total_amount' => abs($amount),
                    'status' => OrderStatus::COMPLETED,
                    'payment_method' => null,
                    'notes' => 'スマレジ取引 #'.$headId,
                    'points_used' => 0,
                    'points_earned' => 0,
                    'smaregi_transaction_head_id' => $headId,
                    'smaregi_transaction_synced_at' => $visitedAt,
                    'smaregi_points_synced_at' => $visitedAt,
                ]);

                $this->saveOrderItems($order, $details);

                return $order;
            });
        } catch (UniqueConstraintViolationException $e) {
            $existingId = $this->orderRepository->findIdBySmaregiTransactionHeadId($projectId, $headId);
            if ($existingId === null) {
                throw $e;
            }

            return Order::query()
                ->withoutGlobalScope(ProjectScope::class)
                ->whereKey($existingId)
                ->first();
        }

        $this->orderNumberAssigner->assignIfMissing((int) $order->id, $projectId);

        Log::info('[Smaregi] Inbound POS order recorded', [
            'order_id' => (int) $order->id,
            'member_id' => (int) $member->id,
            'project_id' => $projectId,
            'transaction_head_id' => $headId,
        ]);

        return $order->fresh();
    }

    public function cancelByTransactionHeadId(int $projectId, string $headId): void
    {
        $orderId = $this->orderRepository->findIdBySmaregiTransactionHeadId($projectId, $headId);
        if ($orderId === null) {
            return;
        }

        Order::query()
            ->withoutGlobalScope(ProjectScope::class)
            ->whereKey($orderId)
            ->where('project_id', $projectId)
            ->where('status', '!=', OrderStatus::CANCELLED)
            ->update(['status' => OrderStatus::CANCELLED]);
    }

    /**
     * @param  list<array<string, string|int|float|bool|array|null>>  $details
     */
    private function saveOrderItems(Order $order, array $details): void
    {
        $productIdBySmaregi = Product::query()
            ->where('project_id', $order->project_id)
            ->whereNotNull('smaregi_product_id')
            ->pluck('id', 'smaregi_product_id')
            ->all();

        foreach ($details as $detail) {
            $productName = trim((string) ($detail['productName'] ?? $detail['printReceiptProductName'] ?? ''));
            if ($productName === '') {
                continue;
            }

            $smaregiProductId = trim((string) ($detail['productId'] ?? ''));
            $quantity = $this->parsePositiveInt($detail['quantity'] ?? 1) ?? 1;
            $unitPrice = $this->parseMoney($detail['salesPrice'] ?? $detail['price'] ?? 0) ?? 0.0;

            $localProductId = null;
            if ($smaregiProductId !== '' && isset($productIdBySmaregi[$smaregiProductId])) {
                $localProductId = (int) $productIdBySmaregi[$smaregiProductId];
            }

            OrderItem::create([
                'project_id' => (int) $order->project_id,
                'order_id' => (int) $order->id,
                'product_id' => $localProductId,
                'product_name' => $productName,
                'smaregi_product_id' => $smaregiProductId !== '' ? $smaregiProductId : null,
                'quantity' => $quantity,
                'price' => $unitPrice,
            ]);
        }
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
