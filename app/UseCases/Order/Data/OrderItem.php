<?php

namespace App\UseCases\Order\Data;

/** 注文明細1件の表示用データ。不変。 */
class OrderItem
{
    public function __construct(
        public int $productId,
        public string $productName,
        public int $quantity,
        public float $price,
        public ?string $notes,
        public ?array $optionIds = null,
    ) {}

    /** @return array{product_id: int, product_name: string, quantity: int, price: float, notes: string|null} */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'notes' => $this->notes,
            'option_ids' => $this->optionIds,
        ];
    }
}
