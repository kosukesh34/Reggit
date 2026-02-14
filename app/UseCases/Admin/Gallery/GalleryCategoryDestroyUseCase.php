<?php

namespace App\UseCases\Admin\Gallery;

use App\Models\GalleryCategory;

class GalleryCategoryDestroyUseCase
{
    public function handle(int $categoryId): void
    {
        $category = GalleryCategory::findOrFail($categoryId);
        $category->delete();
    }
}
