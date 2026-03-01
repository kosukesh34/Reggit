<?php

namespace App\UseCases\Gallery\Data;

use Illuminate\Support\Collection;

/** ギャラリー一覧画面用の表示データ。 @param Collection<int, \App\Models\GalleryCategory> $categories @param Collection<int, \App\Models\GalleryItem> $items */
class GalleryIndexView
{
    public function __construct(
        public Collection $categories,
        public Collection $items
    ) {}
}
