<?php

namespace App\UseCases\Admin\Gallery;

use App\Models\GalleryCategory;
use Illuminate\Database\Eloquent\Collection;

class GalleryCategoryIndexUseCase
{
    /** @return Collection<int, GalleryCategory> */
    public function handle(): Collection
    {
        return GalleryCategory::orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }
}
