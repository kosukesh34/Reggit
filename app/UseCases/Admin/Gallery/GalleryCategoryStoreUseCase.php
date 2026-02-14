<?php

namespace App\UseCases\Admin\Gallery;

use App\Models\GalleryCategory;

class GalleryCategoryStoreUseCase
{
    /**
     * @param array{name: string, sort_order: ?int, is_active: bool} $data
     */
    public function handle(array $data, int $projectId): GalleryCategory
    {
        $data['project_id'] = $projectId;
        return GalleryCategory::create($data);
    }
}
