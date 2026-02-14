<?php

namespace App\UseCases\Admin\Gallery;

use App\Models\GalleryItem;

class GalleryItemStoreUseCase
{
    /**
     * @param array{category_id: ?int, title: ?string, image_url: string, description: ?string, size: ?string, sort_order: ?int, is_active: bool} $data
     */
    public function handle(array $data, int $projectId): GalleryItem
    {
        $data['project_id'] = $projectId;
        return GalleryItem::create($data);
    }
}
