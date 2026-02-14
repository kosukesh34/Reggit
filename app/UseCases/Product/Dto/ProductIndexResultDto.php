<?php

namespace App\UseCases\Product\Dto;

use Illuminate\Support\Collection;

class ProductIndexResultDto
{
    /** @param Collection<int, \App\Models\Product> $products */
    /** @param array<int, ProductCategoryDto> $categories */
    public function __construct(
        public Collection $products,
        public array $categories
    ) {
    }

    public function categoriesToArray(): array
    {
        $payload = [];
        foreach ($this->categories as $category) {
            $payload[] = $category->toArray();
        }
        return $payload;
    }
}
