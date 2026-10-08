<?php

namespace App\UseCases\Order\Data;

use App\Models\Order as OrderModel;

/** Order モデルから表示用 OrderOutput を組み立てる。 */
class OrderFactory
{
    public function create(OrderModel $order): OrderOutput
    {
        $order->loadMissing('items.product');
        $items = [];
        foreach ($order->items as $item) {
            $productName = $item->product_name ?? $item->product?->name ?? '';
            $items[] = new OrderItem(
                $item->product_id,
                $productName,
                $item->quantity,
                (float) $item->price,
                $item->notes,
                $item->option_ids,
            );
        }

        return new OrderOutput(
            $order->id,
            $order->order_number !== null && $order->order_number !== ''
                ? $order->formattedOrderNumber()
                : null,
            (float) $order->total_amount,
            $order->status,
            $order->payment_method,
            (int) $order->points_used,
            (int) $order->points_earned,
            $order->notes,
            $order->order_type,
            $order->created_at?->toIso8601String() ?? now()->toIso8601String(),
            $order->updated_at?->toIso8601String() ?? now()->toIso8601String(),
            $items,
            $order->payment_brand,
            $order->paid_payment_brand,
        );
    }
}
