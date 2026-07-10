<?php

namespace App\Infrastructure\Persistence\Eloquent\Order;

use App\Domain\Order\Repositories\ProductStockRepository;
use App\Models\Product;

class EloquentProductStockRepository implements ProductStockRepository
{
    public function decrease(int $productId, int $projectId, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $product = Product::query()
            ->whereKey($productId)
            ->where('project_id', $projectId)
            ->first();
        if ($product === null || $product->stock === null) {
            return;
        }

        $product->stock = (int) $product->stock - $quantity;
        $product->save();
    }

    public function restore(int $productId, int $projectId, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $product = Product::query()
            ->whereKey($productId)
            ->where('project_id', $projectId)
            ->first();
        if ($product === null || $product->stock === null) {
            return;
        }

        $product->increment('stock', $quantity);
    }
}
