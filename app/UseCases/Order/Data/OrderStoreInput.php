<?php

namespace App\UseCases\Order\Data;

use App\Models\User;

/**
 * 注文作成 UseCase への入力。検証済み。不変。
 *
 * @param  array{
 *     items: array<int, array{product_id: int, quantity: int, notes?: string|null, option_ids?: array<int, int>|null}>,
 *     payment_method: string,
 *     points_used?: int,
 *     notes?: string|null,
 *     order_type?: string|null,
 * }  $orderPayload
 */
class OrderStoreInput
{
    public function __construct(
        public readonly User $user,
        public readonly array $orderPayload,
    ) {}
}
