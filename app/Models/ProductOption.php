<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOption extends Model
{
    use BelongsToProject, HasFactory;

    protected $fillable = [
        'project_id',
        'group_id',
        'name',
        'price_delta',
        'is_default',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'price_delta' => 'decimal:2',
        'is_default' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ProductOptionGroup::class, 'group_id');
    }
}
