<?php

namespace App\UseCases\Admin\Gallery\Data;

use Illuminate\Database\Eloquent\Collection;

/** 管理画面・ギャラリー商品一覧の表示用データ。不変。 */
class GalleryItemIndexView
{
    public function __construct(
        public readonly Collection $items,
        public readonly Collection $categories
    ) {}
}
