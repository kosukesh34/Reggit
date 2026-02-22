<?php

namespace App\UseCases\Product\Data;

/** 商品カテゴリ1件の表示用データ。不変。 */
class ProductCategory
{
    public function __construct(
        public string $key,
        public string $label,
        public string $icon,
        public int $count,
        public int $sort
    ) {
    }

    /** @return array{key: string, label: string, icon: string, count: int, sort: int} */
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
