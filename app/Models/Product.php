<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'ticket_uses',
        'ticket_valid_days',
        'subscription_enabled',
        'subscription_monthly_uses',
        'daily_limit',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'stock' => 'integer',
        'ticket_uses' => 'integer',
        'ticket_valid_days' => 'integer',
        'subscription_enabled' => 'boolean',
        'subscription_monthly_uses' => 'integer',
        'daily_limit' => 'integer',
    ];

    public function isTicket(): bool
    {
        return $this->ticket_uses !== null;
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function optionGroups(): HasMany
    {
        return $this->hasMany(ProductOptionGroup::class)->orderBy('sort_order')->with('options');
    }
}
