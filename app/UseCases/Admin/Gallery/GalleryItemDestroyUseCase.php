<?php

namespace App\UseCases\Admin\Gallery;

use App\Models\GalleryItem;

class GalleryItemDestroyUseCase
{
    public function handle(int $itemId): void
    {
        $item = GalleryItem::findOrFail($itemId);
        $item->delete();
    }
}
