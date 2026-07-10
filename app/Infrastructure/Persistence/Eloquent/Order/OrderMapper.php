<?php

namespace App\Infrastructure\Persistence\Eloquent\Order;

use App\Domain\Order\Aggregates\Order as OrderAggregate;
use App\Domain\Order\ValueObjects\OrderId;
use App\Models\Order as OrderModel;

final class OrderMapper
{
    public static function toDomain(OrderModel $model): OrderAggregate
    {
        return new OrderAggregate(
            new OrderId((int) $model->id),
            (int) $model->project_id,
            (int) $model->user_id,
            (string) $model->order_type,
            (string) $model->payment_method,
            (string) $model->status,
            (int) $model->points_used,
            (int) $model->points_earned,
        );
    }
}
