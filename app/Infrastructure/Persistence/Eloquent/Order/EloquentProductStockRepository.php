<?php

namespace App\Infrastructure\Persistence\Eloquent\Order;

use App\Domain\Order\Exceptions\OrderOperationException;
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
            ->lockForUpdate()
            ->firstOrFail();
        if ($product->stock === null) {
            return;
        }

        if ((int) $product->stock < $quantity) {
            throw new OrderOperationException('商品の在庫が不足しています');
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
