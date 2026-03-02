<?php

namespace App\UseCases\Order\Data;

/** 注文1件の表示用データ（Model の Order と区別するため Output）。 @param OrderItem[] $items */
class OrderOutput
{
    public function __construct(
        public int $id,
        public float $totalAmount,
        public string $status,
        public string $paymentMethod,
        public int $pointsUsed,
        public int $pointsEarned,
        public ?string $notes,
        public string $orderType,
        public string $createdAt,
        public string $updatedAt,
        public array $items
    ) {}

    /** @return array{id: int, total_amount: float, status: string, payment_method: string, points_used: int, points_earned: int, notes: string|null, order_type: string, created_at: string, updated_at: string, items: array<int, array{product_id: int, product_name: string, quantity: int, price: float, notes: string|null}>} */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'total_amount' => $this->totalAmount,
            'status' => $this->status,
            'payment_method' => $this->paymentMethod,
            'points_used' => $this->pointsUsed,
            'points_earned' => $this->pointsEarned,
            'notes' => $this->notes,
            'order_type' => $this->orderType,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'items' => array_map(fn (OrderItem $item) => $item->toArray(), $this->items),
        ];
    }
}
