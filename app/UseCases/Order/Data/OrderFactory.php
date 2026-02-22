<?php

namespace App\UseCases\Order\Data;

use App\Models\Order as OrderModel;

/** Order モデルから表示用 OrderResult を組み立てる。 */
class OrderFactory
{
    public function create(OrderModel $order): OrderResult
    {
        $order->loadMissing('items.product');
        $items = [];
        foreach ($order->items as $item) {
            $productName = $item->product?->name ?? '';
            $items[] = new OrderItem(
                $item->product_id,
                $productName,
                $item->quantity,
                (float) $item->price,
                $item->notes
            );
        }

        return new OrderResult(
            $order->id,
            (float) $order->total_amount,
            $order->status,
            $order->payment_method,
            (int) $order->points_used,
            (int) $order->points_earned,
            $order->notes,
            $order->order_type,
            $order->created_at?->toIso8601String() ?? now()->toIso8601String(),
            $order->updated_at?->toIso8601String() ?? now()->toIso8601String(),
            $items
        );
    }
}
