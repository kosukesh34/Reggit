<?php

namespace App\UseCases\Admin\Gallery\Dto;

use Illuminate\Database\Eloquent\Collection;

class GalleryItemIndexViewDto
{
    public function __construct(
        public readonly Collection $items,
        public readonly Collection $categories
    ) {
    }
}
