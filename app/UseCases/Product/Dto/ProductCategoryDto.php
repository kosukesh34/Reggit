<?php

namespace App\UseCases\Product\Dto;

class ProductCategoryDto
{
    public function __construct(
        public string $key,
        public string $label,
        public string $icon,
        public int $count,
        public int $sort
    ) {
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'icon' => $this->icon,
            'count' => $this->count,
            'sort' => $this->sort,
        ];
    }
}
