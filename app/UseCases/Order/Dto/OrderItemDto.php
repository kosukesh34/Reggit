<?php

namespace App\UseCases\Order\Dto;

class OrderItemDto
{
    public function __construct(
        public int $productId,
        public string $productName,
        public int $quantity,
        public float $price,
        public ?string $notes
    ) {
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'product_name' => $this->productName,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'notes' => $this->notes,
        ];
    }
}
