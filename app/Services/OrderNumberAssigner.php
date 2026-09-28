<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Scopes\StoreAccessScope;
use App\Support\DailySequenceNumber;
use Illuminate\Support\Facades\DB;

/**
 * 注文番号の日次採番。Stripe 未決済では未採番のままにし、決済完了時に付与する。
 */
class OrderNumberAssigner
{
    public function assignIfMissing(int $orderId, int $projectId): ?string
    {
        return DB::transaction(function () use ($orderId, $projectId): ?string {
            $order = Order::query()
                ->whereKey($orderId)
                ->where('project_id', $projectId)
                ->lockForUpdate()
                ->first();
            if ($order === null) {
                return null;
            }
            if ($order->order_number !== null && $order->order_number !== '') {
                return $order->order_number;
            }

            $orderNumber = $this->buildNextOrderNumber($projectId);
            $order->order_number = $orderNumber;
            $order->save();

            return $orderNumber;
        });
    }

    private function buildNextOrderNumber(int $projectId): string
    {
        $prefix = DailySequenceNumber::datePrefix();
        $maxSeq = 0;
        $existing = Order::withoutGlobalScope(StoreAccessScope::class)
            ->where('project_id', $projectId)
            ->where('order_number', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('order_number');

        foreach ($existing as $orderNumber) {
            $seq = DailySequenceNumber::parseSeq((string) $orderNumber);
            if ($seq !== null) {
                $maxSeq = max($maxSeq, $seq);
            }
        }

        return DailySequenceNumber::build(DailySequenceNumber::nextSeq($maxSeq));
    }
}
