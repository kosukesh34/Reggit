<?php

namespace App\UseCases\Gallery\Dto;

use Illuminate\Support\Collection;

class GalleryIndexDto
{
    /** @param Collection<int, \App\Models\GalleryCategory> $categories */
    /** @param Collection<int, \App\Models\GalleryItem> $items */
    public function __construct(
        public Collection $categories,
        public Collection $items
    ) {
    }
}
