<?php

namespace App\Infrastructure\Persistence\Eloquent\Order;

use App\Domain\Order\Repositories\ProductCatalogRepository;
use App\Domain\Order\ValueObjects\ProductForOrder;
use App\Domain\Order\ValueObjects\ProductOptionChoice;
use App\Domain\Order\ValueObjects\ProductOptionGroupSpec;
use App\Models\Product;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class EloquentProductCatalogRepository implements ProductCatalogRepository
{
    public function findForOrder(int $productId, int $projectId): ?ProductForOrder
    {
        $product = Product::with('optionGroups.options')
            ->whereKey($productId)
            ->where('project_id', $projectId)
            ->first();
        if ($product === null) {
            return null;
        }

        return $this->toProductForOrder($product);
    }

    public function findForOrderOrFail(int $productId, int $projectId): ProductForOrder
    {
        $product = $this->findForOrder($productId, $projectId);
        if ($product === null) {
            throw (new ModelNotFoundException)->setModel(Product::class, [$productId]);
        }

        return $product;
    }

    private function toProductForOrder(Product $product): ProductForOrder
    {
        $groups = [];
        foreach ($product->optionGroups as $group) {
            $options = [];
            foreach ($group->options as $option) {
                $options[] = new ProductOptionChoice(
                    (int) $option->id,
                    (string) $option->name,
                    (float) $option->price_delta,
                    (int) $group->id,
                );
            }

            $groups[] = new ProductOptionGroupSpec(
                (int) $group->id,
                (string) $group->name,
                (string) $group->selection_type,
                (bool) $group->is_required,
                (int) $group->min_select,
                $group->max_select !== null ? (int) $group->max_select : null,
                $options,
            );
        }

        return new ProductForOrder(
            (int) $product->id,
            (string) $product->name,
            (float) $product->price,
            (bool) $product->is_available,
            $product->stock !== null ? (int) $product->stock : null,
            $groups,
            $product->isTicket(),
        );
    }
}
