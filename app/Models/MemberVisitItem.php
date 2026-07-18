<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberVisitItem extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'member_visit_id',
        'product_id',
        'smaregi_product_id',
        'product_name',
        'quantity',
        'unit_price',
        'amount',
        'sort_order',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'float',
        'amount' => 'float',
        'sort_order' => 'integer',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(MemberVisit::class, 'member_visit_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
