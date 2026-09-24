<?php

namespace App\UseCases\Product\Data;

/** 商品一覧 UseCase の出力。 */
class ProductIndexOutput
{
    /**
     * @param  list<array<string, mixed>>  $products
     * @param  array<int, ProductCategory>  $categories
     */
    public function __construct(
        public array $products,
        public array $categories
    ) {}

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
