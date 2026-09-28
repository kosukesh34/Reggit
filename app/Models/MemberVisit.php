<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberVisit extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id', 'store_id',
        'member_id',
        'visited_at',
        'source',
        'order_id',
        'note',
        'amount',
        'smaregi_transaction_head_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'visited_at' => 'datetime',
        'amount' => 'float',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MemberVisitItem::class)->orderBy('sort_order');
    }
}
