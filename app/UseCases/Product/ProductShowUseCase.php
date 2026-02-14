<?php

namespace App\UseCases\Product;

use App\Models\Product;

class ProductShowUseCase
{
    public function handle(int $id): Product
    {
        return Product::findOrFail($id);
    }
}
