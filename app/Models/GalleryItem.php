<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GalleryItem extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'category_id',
        'title',
        'image_url',
        'description',
        'size',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GalleryCategory::class, 'category_id');
    }
}
