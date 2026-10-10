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
        $date = today();
        $day = $date->toDateString();
        // A no-op upsert takes the row's exclusive lock immediately. INSERT IGNORE
        // would take shared duplicate-key locks and deadlock on their upgrade.
        DB::table('order_daily_sequences')->upsert([[
            'project_id' => $projectId, 'date' => $day, 'last_sequence' => 0,
        ]], ['project_id', 'date'], ['date']);
        $counter = DB::table('order_daily_sequences')->where('project_id', $projectId)->where('date', $day);
        $maxSeq = (int) $counter->lockForUpdate()->firstOrFail()->last_sequence;
        if ($maxSeq === 0) {
            // Bootstrap existing numbers using a current read. The range index excludes
            // unnumbered orders whose owners may already be waiting for this counter.
            $existing = Order::withoutGlobalScope(StoreAccessScope::class)
                ->forceIndex('orders_project_id_order_number_unique')
                ->where('project_id', $projectId)
                ->where('order_number', 'like', DailySequenceNumber::datePrefix($date).'%')
                ->lockForUpdate()->pluck('order_number');
            foreach ($existing as $orderNumber) {
                $seq = DailySequenceNumber::parseSeq((string) $orderNumber, $date);
                if ($seq !== null) {
                    $maxSeq = max($maxSeq, $seq);
                }
            }
        }
        $next = DailySequenceNumber::nextSeq($maxSeq);
        $counter->update(['last_sequence' => $next]);

        return DailySequenceNumber::build($next, $date);
    }
}
