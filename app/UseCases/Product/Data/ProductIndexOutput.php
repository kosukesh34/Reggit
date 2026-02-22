<?php

namespace App\UseCases\Product\Data;

use Illuminate\Support\Collection;

/** 商品一覧 UseCase の出力。 @param Collection<int, \App\Models\Product> $products @param array<int, ProductCategory> $categories */
class ProductIndexOutput
{
    public function __construct(
        public Collection $products,
        public array $categories
    ) {
    }

    /** @return array<int, array{key: string, label: string, icon: string, count: int, sort: int}> */
    public function categoriesToArray(): array
    {
        $payload = [];
        foreach ($this->categories as $category) {
            $payload[] = $category->toArray();
        }
        return $payload;
    }
}
