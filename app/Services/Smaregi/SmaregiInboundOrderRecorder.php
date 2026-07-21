<?php

namespace App\Services\Smaregi;

use App\Domain\Order\Repositories\OrderRepository;
use App\Enums\OrderStatus;
use App\Enums\OrderType;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
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
            $order = DB::transaction(function () use ($headId, $amount, $details, $visitedAt, $projectId, $userId): Order {
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
        $productIdBySmaregi = SmaregiInboundDetailHelper::productIdsBySmaregiProductId((int) $order->project_id);

        foreach ($details as $detail) {
            $lineItem = SmaregiInboundDetailHelper::parseLineItem($detail);
            if ($lineItem === null) {
                continue;
            }

            OrderItem::create([
                'project_id' => (int) $order->project_id,
                'order_id' => (int) $order->id,
                'product_id' => SmaregiInboundDetailHelper::resolveLocalProductId(
                    $lineItem['smaregiProductId'],
                    $productIdBySmaregi,
                ),
                'product_name' => $lineItem['productName'],
                'smaregi_product_id' => SmaregiInboundDetailHelper::nullableSmaregiProductId($lineItem['smaregiProductId']),
                'quantity' => $lineItem['quantity'],
                'price' => $lineItem['unitPrice'],
            ]);
        }
    }
}
