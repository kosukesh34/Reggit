<?php

namespace App\UseCases\Admin\Gallery;

use App\Models\GalleryCategory;
use App\Models\GalleryItem;
use App\UseCases\Admin\Gallery\Data\GalleryItemIndexView;

class GalleryItemIndexUseCase
{
    public function handle(?int $categoryId): GalleryItemIndexView
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

        return new GalleryItemIndexView(
            $query->get(),
            $categories
        );
    }
}
