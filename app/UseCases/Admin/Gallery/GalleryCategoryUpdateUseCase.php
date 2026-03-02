<?php

namespace App\UseCases\Admin\Gallery;

use App\Models\GalleryCategory;

class GalleryCategoryUpdateUseCase
{
    /**
     * @param  array{name: string, sort_order: ?int, is_active: bool}  $data
     */
    public function handle(int $categoryId, array $data): GalleryCategory
    {
        $category = GalleryCategory::findOrFail($categoryId);
        $category->update($data);

        return $category;
    }
}
