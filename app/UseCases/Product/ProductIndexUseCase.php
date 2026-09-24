<?php

namespace App\UseCases\Product;

use App\UseCases\Product\Contracts\ProductReadRepository;
use App\UseCases\Product\Data\ProductCategory;
use App\UseCases\Product\Data\ProductIndexOutput;

class ProductIndexUseCase
{
    public function __construct(private ProductReadRepository $products) {}

    private const CATEGORY_META = [
        'food' => ['label' => 'フード', 'icon' => 'fa-utensils', 'sort' => 1],
        'drink' => ['label' => 'ドリンク', 'icon' => 'fa-coffee', 'sort' => 2],
        'dessert' => ['label' => 'デザート', 'icon' => 'fa-ice-cream', 'sort' => 3],
        'side' => ['label' => 'サイドメニュー', 'icon' => 'fa-french-fries', 'sort' => 4],
        'other' => ['label' => 'その他', 'icon' => 'fa-ellipsis-h', 'sort' => 99],
    ];

    public function handle(?string $category, ?bool $availableOnly, bool $includeCategories): ProductIndexOutput
    {
        $products = $this->products->list($category, $availableOnly);

        if ($includeCategories === false) {
            return new ProductIndexOutput($products, []);
        }

        $categoryCounts = $this->products->availableCategoryCounts();

        $categories = [];
        $categories[] = new ProductCategory(
            'all',
            'すべて',
            'fa-th-large',
            array_sum($categoryCounts),
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
