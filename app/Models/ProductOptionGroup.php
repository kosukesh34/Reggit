<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductOptionGroup extends Model
{
    use BelongsToProject, HasFactory;

    public const TYPE_SINGLE = 'single';

    public const TYPE_MULTIPLE = 'multiple';

    protected $fillable = [
        'project_id',
        'product_id',
        'name',
        'selection_type',
        'is_required',
        'min_select',
        'max_select',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'is_required' => 'boolean',
        'min_select' => 'integer',
        'max_select' => 'integer',
        'sort_order' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class, 'group_id')->orderBy('sort_order');
    }
}
