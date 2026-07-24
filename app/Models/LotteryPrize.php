<?php

namespace App\Models;

use App\Models\Concerns\BelongsToProject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotteryPrize extends Model
{
    use BelongsToProject;

    protected $fillable = [
        'project_id',
        'reward_type',
        'coupon_id',
        'is_lose',
        'probability',
        'sort_order',
        'is_active',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'probability' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_lose' => 'boolean',
    ];

    public function isLose(): bool
    {
        return $this->is_lose === true || $this->coupon_id === null;
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}
