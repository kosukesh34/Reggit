<?php

namespace App\UseCases\Order\Dto;

use App\Models\Order;

class OrderDtoFactory
{
    public function create(Order $order): OrderDto
    {
        $order->loadMissing('items.product');
        $items = [];
        foreach ($order->items as $item) {
            $productName = $item->product?->name ?? '';
            $items[] = new OrderItemDto(
                $item->product_id,
                $productName,
                $item->quantity,
                (float) $item->price,
                $item->notes
            );
        }

        return new OrderDto(
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
