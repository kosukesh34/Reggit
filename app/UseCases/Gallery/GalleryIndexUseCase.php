<?php

namespace App\UseCases\Gallery;

use App\Models\GalleryCategory;
use App\Models\GalleryItem;
use App\UseCases\Gallery\Dto\GalleryIndexDto;
use App\Util\AdminContext;

class GalleryIndexUseCase
{
    public function handle(?int $categoryId): GalleryIndexDto
    {
        $projectId = AdminContext::getCurrentProjectId() ?? 1;

        $categories = GalleryCategory::where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $itemsQuery = GalleryItem::where('project_id', $projectId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($categoryId !== null) {
            $itemsQuery->where('category_id', $categoryId);
        }

        $items = $itemsQuery->get();

        return new GalleryIndexDto($categories, $items);
    }
}
