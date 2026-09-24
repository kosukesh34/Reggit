<?php

namespace App\Infrastructure\Persistence\Eloquent\Product;

use App\Models\Product;
use App\UseCases\Product\Contracts\ProductReadRepository;
use Illuminate\Support\Facades\DB;

class EloquentProductReadRepository implements ProductReadRepository
{
    public function list(?string $category, ?bool $availableOnly): array
    {
        $query = Product::query()->with('optionGroups.options');
        if ($availableOnly === true) {
            $query->where('is_available', true);
        }
        if ($category !== null && $category !== '' && $category !== 'all') {
            $query->where('category', $category);
        }

        return $query->orderBy('category')->orderBy('name')->get()
            ->map(fn (Product $product): array => $product->toArray())
            ->all();
    }

    public function findOrFail(int $id): array
    {
        return Product::with('optionGroups.options')->findOrFail($id)->toArray();
    }

    public function availableCategoryCounts(): array
    {
        return Product::query()
            ->select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category')
            ->map(fn ($count): int => (int) $count)
            ->all();
    }
}
