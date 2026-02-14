<?php

namespace App\UseCases\Admin\Gallery;

use App\Models\GalleryCategory;
use App\Models\GalleryItem;
use App\UseCases\Admin\Gallery\Dto\GalleryItemIndexViewDto;

class GalleryItemIndexUseCase
{
    public function handle(?int $categoryId): GalleryItemIndexViewDto
    {
        $categories = GalleryCategory::orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $query = GalleryItem::with('category')
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        return new GalleryItemIndexViewDto(
            $query->get(),
            $categories
        );
    }
}
