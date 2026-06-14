<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use BelongsToProject, HasFactory;

    protected $fillable = [
        'project_id',
        'smaregi_product_id',
        'name',
        'description',
        'price',
        'image_url',
        'category',
        'is_available',
        'stock',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'stock' => 'integer',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function optionGroups()
    {
        return $this->hasMany(ProductOptionGroup::class)->orderBy('sort_order')->with('options');
    }
}
