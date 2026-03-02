<?php

namespace App\UseCases\Admin\Gallery;

use App\Models\GalleryItem;

class GalleryItemUpdateUseCase
{
    /**
     * @param  array{category_id: ?int, title: ?string, image_url: string, description: ?string, size: ?string, sort_order: ?int, is_active: bool}  $data
     */
    public function handle(int $itemId, array $data): GalleryItem
    {
        $item = GalleryItem::findOrFail($itemId);
        $item->update($data);

        return $item;
    }
}
