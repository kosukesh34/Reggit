<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberStampTransaction extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id', 'store_id', 'origin',
        'member_id',
        'stamp_card_id',
        'delta',
        'balance_after',
        'description',
        'order_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'delta' => 'integer',
        'balance_after' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function stampCard(): BelongsTo
    {
        return $this->belongsTo(StampCard::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
