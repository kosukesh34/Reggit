<?php

namespace App\UseCases\Product;

use App\UseCases\Product\Contracts\ProductReadRepository;

class ProductShowUseCase
{
    public function __construct(private ProductReadRepository $products) {}

    /** @return array<string, mixed> */
    public function handle(int $id): array
    {
        return $this->products->findOrFail($id);
    }
}
