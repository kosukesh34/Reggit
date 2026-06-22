<?php

namespace App\UseCases\Product;

use App\Models\Product;
use App\UseCases\Product\Data\ProductCategory;
use App\UseCases\Product\Data\ProductIndexOutput;
use Illuminate\Support\Facades\DB;

class ProductIndexUseCase
{
    private const CATEGORY_META = [
        'food' => ['label' => 'フード', 'icon' => 'fa-utensils', 'sort' => 1],
        'drink' => ['label' => 'ドリンク', 'icon' => 'fa-coffee', 'sort' => 2],
        'dessert' => ['label' => 'デザート', 'icon' => 'fa-ice-cream', 'sort' => 3],
        'side' => ['label' => 'サイドメニュー', 'icon' => 'fa-french-fries', 'sort' => 4],
        'other' => ['label' => 'その他', 'icon' => 'fa-ellipsis-h', 'sort' => 99],
    ];

    public function handle(?string $category, ?bool $availableOnly, bool $includeCategories): ProductIndexOutput
    {
        $query = Product::query()->with('optionGroups.options');
        if ($availableOnly === true) {
            $query->where('is_available', true);
        }

        if ($category !== null && $category !== '' && $category !== 'all') {
            $query->where('category', $category);
        }

        $products = $query->orderBy('category')->orderBy('name')->get();

        if ($includeCategories === false) {
            return new ProductIndexOutput($products, []);
        }

        $categoryCounts = Product::query()
            ->where('is_available', true)
            ->select('category', DB::raw('COUNT(*) as count'))
            ->groupBy('category')
            ->pluck('count', 'category');

        $categories = [];
        $categories[] = new ProductCategory(
            'all',
            'すべて',
            'fa-th-large',
            $categoryCounts->sum(),
            0
        );

        foreach ($categoryCounts as $catKey => $count) {
            $meta = self::CATEGORY_META[$catKey] ?? self::CATEGORY_META['other'];
            $categories[] = new ProductCategory(
                $catKey,
                $meta['label'],
                $meta['icon'],
                (int) $count,
                $meta['sort']
            );
        }

        usort($categories, fn (ProductCategory $a, ProductCategory $b) => $a->sort <=> $b->sort);

        return new ProductIndexOutput($products, $categories);
    }
}
